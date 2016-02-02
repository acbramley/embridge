<?php
/**
 * @file
 * Contains \Drupal\embridge\Element\EmbridgeAsset.
 */

namespace Drupal\embridge\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\embridge\Entity\EmbridgeAssetEntity;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("embridge_asset")
 */
class EmbridgeAsset extends FormElement {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processEmbridgeAsset'],
      ],
      '#element_validate' => [
        [$class, 'validateEmbridgeAsset'],
      ],
      '#pre_render' => [
        [$class, 'preRenderEmbridgeAsset'],
      ],
      '#theme' => 'file_managed_file',
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE,
      '#extended' => FALSE,
      '#attached' => [
        'library' => ['file/drupal.file'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $fids = !empty($input['fids']) ? explode(' ', $input['fids']) : [];
    foreach ($fids as $key => $fid) {
      $fids[$key] = (int) $fid;
    }

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      $input['fids'] = $fids;
      $return = $input;

      // Uploads take priority over all other values.
      if ($files = self::saveFileUpload($element, $form_state)) {
        if ($element['#multiple']) {
          $fids = array_merge($fids, array_keys($files));
        }
        else {
          $fids = array_keys($files);
        }
      }
      else {
        // Check for #filefield_value_callback values.
        // Because FAPI does not allow multiple #value_callback values like it
        // does for #element_validate and #process, this fills the missing
        // functionality to allow File fields to be extended through FAPI.
        if (isset($element['#file_value_callbacks'])) {
          foreach ($element['#file_value_callbacks'] as $callback) {
            $callback($element, $input, $form_state);
          }
        }

        // Load files if the FIDs have changed to confirm they exist.
        if (!empty($input['fids'])) {
          $fids = [];
          foreach ($input['fids'] as $fid) {
            if ($file = EmbridgeAssetEntity::load($fid)) {
              $fids[] = $file->id();
            }
          }
        }
      }
    }

    // If there is no input, set the default value.
    else {
      if ($element['#extended']) {
        $default_fids = isset($element['#default_value']['fids']) ? $element['#default_value']['fids'] : [];
        $return = isset($element['#default_value']) ? $element['#default_value'] : ['fids' => []];
      }
      else {
        $default_fids = isset($element['#default_value']) ? $element['#default_value'] : [];
        $return = ['fids' => []];
      }

      // Confirm that the file exists when used as a default value.
      if (!empty($default_fids)) {
        $fids = [];
        foreach ($default_fids as $fid) {
          if ($file = EmbridgeAssetEntity::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }

    $return['fids'] = $fids;
    return $return;
  }

  /**
   * Render API callback: Expands the embridge_asset element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
  public static function processEmbridgeAsset(&$element, FormStateInterface $form_state, &$complete_form) {

    // This is used sometimes so let's implode it just once.
    $parents_prefix = implode('_', $element['#parents']);

    $fids = isset($element['#value']['fids']) ? $element['#value']['fids'] : [];

    // Set some default element properties.
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($fids) ? EmbridgeAssetEntity::loadMultiple($fids) : FALSE;
    $element['#tree'] = TRUE;

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $ajax_settings = [
      'callback' => [get_called_class(), 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    // Set up the buttons first since we need to check if they were clicked.
    $element['upload_button'] = [
      '#name' => $parents_prefix . '_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => -5,
    ];

    // Force the progress indicator for the remove button to be either 'none' or
    // 'throbber', even if the upload button is using something else.
    $ajax_settings['progress']['type'] = ($element['#progress_indicator'] == 'none') ? 'none' : 'throbber';
    $ajax_settings['progress']['message'] = NULL;
    $ajax_settings['effect'] = 'none';
    $element['remove_button'] = [
      '#name' => $parents_prefix . '_remove_button',
      '#type' => 'submit',
      '#value' => $element['#multiple'] ? t('Remove selected') : t('Remove'),
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => 1,
    ];

    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fids,
    ];

    // Add progress bar support to the upload if possible.
    if ($element['#progress_indicator'] == 'bar' && $implementation = file_progress_implementation()) {
      $upload_progress_key = mt_rand();

      if ($implementation == 'uploadprogress') {
        $element['UPLOAD_IDENTIFIER'] = [
          '#type' => 'hidden',
          '#value' => $upload_progress_key,
          '#attributes' => ['class' => ['file-progress']],
          // Uploadprogress extension requires this field to be at the top of
          // the form.
          '#weight' => -20,
        ];
      }
      elseif ($implementation == 'apc') {
        $element['APC_UPLOAD_PROGRESS'] = [
          '#type' => 'hidden',
          '#value' => $upload_progress_key,
          '#attributes' => ['class' => ['file-progress']],
          // Uploadprogress extension requires this field to be at the top of
          // the form.
          '#weight' => -20,
        ];
      }

      // Add the upload progress callback.
      $element['upload_button']['#ajax']['progress']['url'] = Url::fromRoute('file.ajax_progress');
    }

    // The file upload field itself.
    $element['upload'] = [
      '#name' => 'files[' . $parents_prefix . ']',
      '#type' => 'file',
      '#title' => t('Choose a file'),
      '#title_display' => 'invisible',
      '#size' => $element['#size'],
      '#multiple' => $element['#multiple'],
      '#theme_wrappers' => [],
      '#weight' => -10,
      '#error_no_message' => TRUE,
    ];

    if (!empty($fids) && $element['#files']) {
      foreach ($element['#files'] as $delta => $file) {
        $file_link = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
        if ($element['#multiple']) {
          $element['file_' . $delta]['selected'] = [
            '#type' => 'checkbox',
            '#title' => \Drupal::service('renderer')->renderPlain($file_link),
          ];
        }
        else {
          $element['file_' . $delta]['filename'] = $file_link + ['#weight' => -10];
        }
      }
    }

    // Add the extension list to the page as JavaScript settings.
    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $extension_list = implode(',', array_filter(explode(' ', $element['#upload_validators']['file_validate_extensions'][0])));
      $element['upload']['#attached']['drupalSettings']['file']['elements']['#' . $element['#id']] = $extension_list;
    }

    // Let #id point to the file element, so the field label's 'for' corresponds
    // with it.
    $element['#id'] = &$element['upload']['#id'];

    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Render API callback: Hides display of the upload or remove controls.
   *
   * Upload controls are hidden when an asset is already uploaded. Remove controls
   * are hidden when there is no file attached. Controls are hidden here instead
   * of in \Drupal\file\Element\ManagedFile::processManagedFile(), because
   * #access for these buttons depends on the managed_file element's #value. See
   * the documentation of \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   * for more detailed information about the relationship between #process,
   * #value, and #access.
   *
   * Because #access is set here, it affects display only and does not prevent
   * JavaScript or other untrusted code from submitting the form as though
   * access were enabled. The form processing functions for these elements
   * should not assume that the buttons can't be "clicked" just because they are
   * not displayed.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderEmbridgeAsset($element) {
    // If we already have a file, we don't want to show the upload controls.
    if (!empty($element['#value']['fids'])) {
      if (!$element['#multiple']) {
        $element['upload']['#access'] = FALSE;
        $element['upload_button']['#access'] = FALSE;
      }
    }
    // If we don't already have a file, there is nothing to remove.
    else {
      $element['remove_button']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Render API callback: Validates the managed_file element.
   */
  public static function validateEmbridgeAsset(&$element, FormStateInterface $form_state, &$complete_form) {
    // If referencing an existing file, only allow if there are existing
    // references. This prevents unmanaged files from being deleted if this
    // item were to be deleted.
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);

    // Check required property based on the FID.
    if ($element['#required'] && empty($element['fids']['#value']) && !in_array($clicked_button, ['upload_button', 'remove_button'])) {
      // We expect the field name placeholder value to be wrapped in t()
      // here, so it won't be escaped again as it's already marked safe.
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Consolidate the array value of this field to array of FIDs.
    if (!$element['#extended']) {
      $form_state->setValueForElement($element, $element['fids']['#value']);
    }
  }

  /**
   * Saves any files that have been uploaded into a managed_file element.
   *
   * @param array $element
   *   The FAPI element whose values are being saved.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of file entities for each file that was saved, keyed by its file
   *   ID, or FALSE if no files were saved.
   */
  public static function saveFileUpload($element, FormStateInterface $form_state) {
    $upload_name = implode('_', $element['#parents']);
    $file_upload = \Drupal::request()->files->get("files[$upload_name]", NULL, TRUE);
    if (empty($file_upload)) {
      return FALSE;
    }

    $destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;
    if (isset($destination) && !file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      \Drupal::logger('file')->notice('The upload directory %directory for the file field !name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', array('%directory' => $destination, '!name' => $element['#field_name']));
      $form_state->setError($element, t('The file could not be uploaded.'));
      return FALSE;
    }

    // Save attached files to the database.
    $files_uploaded = $element['#multiple'] && count(array_filter($file_upload)) > 0;
    $files_uploaded |= !$element['#multiple'] && !empty($file_upload);
    if ($files_uploaded) {
      if (!$files = file_save_upload($upload_name, $element['#upload_validators'], $destination)) {
        \Drupal::logger('file')->notice('The file upload failed. %upload', array('%upload' => $upload_name));
        $form_state->setError($element, t('Files in the @name field were unable to be uploaded.', array('@name' => $element['#title'])));
        return array();
      }

      // Value callback expects FIDs to be keys.
      $files = array_filter($files);
      $fids = array_map(function($file) { return $file->id(); }, $files);

      return empty($files) ? array() : array_combine($fids, $files);
    }

    return array();
  }

}