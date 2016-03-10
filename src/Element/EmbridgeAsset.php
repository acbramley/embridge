<?php
/**
 * @file
 * Contains \Drupal\embridge\Element\EmbridgeAsset.
 */

namespace Drupal\embridge\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\Request;


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
   * Form submit handler for upload/remove buttons of embridge_asset elements.
   *
   * Copied from file_managed_file_submit.
   *
   * @see \Drupal\embridge\Element\EmbridgeAsset::processManagedFile()
   */
  public static function submitHandler($form, FormStateInterface $form_state) {
    // Determine whether it was the upload or remove button that was clicked,
    // and set $element to the managed_file element that contains that button.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    // No action is needed here for the upload button, because all file uploads
    // on the form are processed by EmbridgeAsset::valueCallback()
    // regardless of which button was clicked. Action is needed here for the
    // remove button, because we only remove a file in response to its remove
    // button being clicked.
    if ($button_key == 'remove_button') {
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface[] $assets */
      $assets = $element['#files'];
      $entity_ids = array_keys($assets);
      // Get files that will be removed.
      if ($element['#multiple']) {
        $ids_to_remove = [];
        foreach (Element::children($element) as $name) {
          if (strpos($name, 'file_') === 0 && $element[$name]['selected']['#value']) {
            $ids_to_remove[] = (int) substr($name, 5);
          }
        }
        $entity_ids = array_diff($entity_ids, $ids_to_remove);
      }
      else {
        // If we deal with single upload element remove the file and set
        // element's value to empty array (file could not be removed from
        // element if we don't do that).
        $ids_to_remove = $entity_ids;
        $entity_ids = array();
      }

      foreach ($ids_to_remove as $id) {
        // If it's a temporary file we can safely remove it immediately.
        if ($assets[$id] && $assets[$id]->isTemporary()) {
          $assets[$id]->delete();
        }
      }
      // Update both $form_state->getValues() and FormState::$input to reflect
      // that the file has been removed, so that the form is rebuilt correctly.
      // $form_state->getValues() must be updated in case additional submit
      // handlers run, and for form building functions that run during the
      // rebuild, such as when the managed_file element is part of a field
      // widget.FormState::$input must be updated so that
      // EmbridgeAsset::valueCallback() has correct information
      // during the rebuild.
      $form_state->setValueForElement($element['fids'], implode(' ', $entity_ids));
      NestedArray::setValue($form_state->getUserInput(), $element['fids']['#parents'], implode(' ', $entity_ids));
    }

    // Set the form to rebuild so that $form is correctly updated in response to
    // processing the file removal. Since this function did not change
    // $form_state if the upload button was clicked, a rebuild isn't necessary
    // in that situation and calling $form_state->disableRedirect() would
    // suffice. However, we choose to always rebuild, to keep the form
    // processing workflow consistent between the two buttons.
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for embridge_asset upload forms.
   *
   * This ajax callback takes care of the following things:
   *   - Ensures that broken requests due to too big files are caught.
   *   - Adds a class to the response to be able to highlight in the UI, that a
   *     new file got uploaded.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    // Add the special AJAX class if a new file was added.
    $current_file_count = $form_state->get('file_upload_delta_initial');
    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
    }
    // Otherwise just add the new content class on a placeholder.
    else {
      $form['#suffix'] .= '<span class="ajax-new-content"></span>';
    }

    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

  /**
   * Render API callback: Expands the embridge_asset element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The processed element.
   */
  public static function processEmbridgeAsset(array &$element, FormStateInterface $form_state, array &$complete_form) {

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
      '#submit' => [[get_called_class(), 'submitHandler']],
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
      '#submit' => [[get_called_class(), 'submitHandler']],
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

    // Build link for dialog.
    if ($element['#allow_search'] && \Drupal::currentUser()->hasPermission('search embridge assets')) {
      $url_options = [
        'field_config' => $element['#field_config'],
        'delta' => $element['#delta'],
      ];
      $link_url = Url::fromRoute('embridge.search.modal', $url_options);
      $link_url->setOptions(
        array(
          'attributes' => array(
            'class' => array('use-ajax', 'button'),
            'data-accepts' => 'application/vnd.drupal-modal',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(array(
              'width' => 1000,
            )),
          ),
        )
      );
      $modal_link = Link::fromTextAndUrl('Asset search', $link_url);

      // TODO: Why can't we just have another element?
      $element['upload_button']['#suffix'] = $modal_link->toString();
    }

    if (!empty($fids) && $element['#files']) {
      foreach ($element['#files'] as $delta => $file) {
        $file_link = [
          '#theme' => 'embridge_file_link',
          '#asset' => $file,
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
   * Upload controls are hidden when an assets already uploaded. Remove controls
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
   * @param array $element
   *   The element.
   *
   * @return array
   *   The processed element.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderEmbridgeAsset(array $element) {
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
   * Validate the element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   */
  public static function validateEmbridgeAsset(array &$element, FormStateInterface $form_state, array &$complete_form) {
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
      if (!$assets = self::saveUpload($upload_name, $element['#catalog_id'], $element['#upload_validators'], $destination)) {
        \Drupal::logger('file')->notice('The file upload failed. %upload', array('%upload' => $upload_name));
        $form_state->setError($element, t('Files in the @name field were unable to be uploaded.', array('@name' => $element['#title'])));
        return array();
      }

      // Value callback expects FIDs to be keys.
      $assets = array_filter($assets);
      $fids = array_map(function($asset) {
        return $asset->id();
      }, $assets);

      return empty($assets) ? array() : array_combine($fids, $assets);
    }

    return array();
  }

  /**
   * Uploads the file to EMDB.
   *
   * @param string $form_field_name
   *   A string that is the associative array key of the upload form element in
   *   the form array.
   * @param string $catalog_id
   *   The catalog id for the catalog we are uploading to.
   * @param array $validators
   *   An optional, associative array of callback functions used to validate the
   *   file.
   * @param string|bool $destination_dir
   *   A string containing the URI that the file should be copied to. This must
   *   be a stream wrapper URI. If this value is omitted, Drupal's temporary
   *   files scheme will be used ("temporary://").
   * @param int $delta
   *   Delta of the file to save or NULL to save all files. Defaults to NULL.
   * @param int $replace
   *   Replace behavior when the destination file already exists:
   *   - FILE_EXISTS_REPLACE: Replace the existing file.
   *   - FILE_EXISTS_RENAME: Append _{incrementing number} until the filename is
   *     unique.
   *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface[]
   *   Function returns array of files or a single file object if $delta
   *   != NULL. Each file object contains the file information if the
   *   upload succeeded or FALSE in the event of an error. Function
   *   returns NULL if no file was uploaded.
   *
   *   The docs for the "File interface" group, which you can find under
   *   Related topics, or the header at the top of this file, documents the
   *   components of a file entity. In addition to the standard components,
   *   this function adds:
   *   - source: Path to the file before it is moved.
   *   - destination: Path to the file after it is moved (same as 'uri').
   */
  public static function saveUpload($form_field_name, $catalog_id, $validators = array(), $destination_dir = FALSE, $delta = NULL, $replace = FILE_EXISTS_RENAME) {
    $user = \Drupal::currentUser();
    static $upload_cache;

    $file_upload = \Drupal::request()->files->get("files[$form_field_name]", NULL, TRUE);
    // Make sure there's an upload to process.
    if (empty($file_upload)) {
      return NULL;
    }

    // Return cached objects without processing since the file will have
    // already been processed and the paths in $_FILES will be invalid.
    if (isset($upload_cache[$form_field_name])) {
      if (isset($delta)) {
        return $upload_cache[$form_field_name][$delta];
      }
      return $upload_cache[$form_field_name];
    }

    // Prepare uploaded files info. Representation is slightly different
    // for multiple uploads and we fix that here.
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $uploaded_files */
    $uploaded_files = $file_upload;
    if (!is_array($file_upload)) {
      $uploaded_files = array($file_upload);
    }

    $assets = array();
    foreach ($uploaded_files as $i => $file_info) {
      // Check for file upload errors and return FALSE for this file if a lower
      // level system error occurred. For a complete list of errors:
      // See http://php.net/manual/features.file-upload.errors.php.
      switch ($file_info->getError()) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          drupal_set_message(t('The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $file_info->getFilename(), '%maxsize' => format_size(file_upload_max_size()))), 'error');
          $assets[$i] = FALSE;
          continue;

        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          drupal_set_message(t('The file %file could not be saved because the upload did not complete.', array('%file' => $file_info->getFilename())), 'error');
          $assets[$i] = FALSE;
          continue;

        case UPLOAD_ERR_OK:
          // Final check that this is a valid upload, if it isn't, use the
          // default error handler.
          if (is_uploaded_file($file_info->getRealPath())) {
            break;
          }

        default:
          // Unknown error.
          drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => $file_info->getFilename())), 'error');
          $assets[$i] = FALSE;
          continue;

      }
      // Begin building file entity.
      $values = array(
        'uid' => $user->id(),
        'filename' => $file_info->getClientOriginalName(),
        'filesize' => $file_info->getSize(),
        'catalog_id' => $catalog_id,
      );
      $values['filemime'] = \Drupal::service('file.mime_type.guesser')->guess($values['filename']);

      // Create our Embridge Entity.
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $asset */
      $asset = EmbridgeAssetEntity::create($values);

      $extensions = '';
      if (isset($validators['embridge_asset_validate_file_extensions'])) {
        if (isset($validators['embridge_asset_validate_file_extensions'][0])) {
          // Build the list of non-munged exts if the caller provided them.
          $extensions = $validators['embridge_asset_validate_file_extensions'][0];
        }
        else {
          // If 'file_validate_extensions' is set and the list is empty then the
          // caller wants to allow any extension. In this case we have to remove
          // the validator or else it will reject all extensions.
          unset($validators['embridge_asset_validate_file_extensions']);
        }
      }
      else {
        // No validator was provided, so add one using the default list.
        // Build a default non-munged safe list for file_munge_filename().
        $extensions = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp';
        $validators['embridge_asset_validate_file_extensions'] = array();
        $validators['embridge_asset_validate_file_extensions'][0] = $extensions;
      }

      if (!empty($extensions)) {
        // Munge the filename to protect against possible malicious extension
        // hiding within an unknown file type (ie: filename.html.foo).
        $asset->setFilename(file_munge_filename($asset->getFilename(), $extensions));
      }

      // Rename potentially executable files, to help prevent exploits.
      if (!\Drupal::config('system.file')->get('allow_insecure_uploads') && preg_match('/\.(php|pl|py|cgi|asp|js)(\.|$)/i', $asset->getFilename()) && (substr($asset->getFilename(), -4) != '.txt')) {
        $asset->setMimeType('text/plain');
        // The destination filename will also later be used to create the URI.
        $asset->setFilename($asset->getFilename() . '.txt');
        // The .txt extension may not be in the allowed list of extensions.
        // We have to add it here or else the file upload will fail.
        if (!empty($extensions)) {
          $validators['embridge_asset_validate_file_extensions'][0] .= ' txt';
          drupal_set_message(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $asset->getFilename())));
        }
      }

      // If the destination is not provided, use the temporary directory.
      if (empty($destination_dir)) {
        $destination_dir = 'temporary://';
      }

      // Assert that the destination contains a valid stream.
      $destination_scheme = file_uri_scheme($destination_dir);
      if (!file_stream_wrapper_valid_scheme($destination_scheme)) {
        drupal_set_message(t('The file could not be uploaded because the destination %destination is invalid.', array('%destination' => $destination_dir)), 'error');
        $assets[$i] = FALSE;
        continue;
      }

      // A file URI may already have a trailing slash or look like "public://".
      if (substr($destination_dir, -1) != '/') {
        $destination_dir .= '/';
      }
      $asset_destination = file_destination($destination_dir . $asset->getFilename(), $replace);
      // If file_destination() returns FALSE then $replace === FILE_EXISTS_ERROR
      // and there's an existing file so we need to bail.
      if ($asset_destination === FALSE) {
        drupal_set_message(t('The file %source could not be uploaded because a file by that name already exists in the destination %directory.', array('%source' => $form_field_name, '%directory' => $destination_dir)), 'error');
        $assets[$i] = FALSE;
        continue;
      }

      // Add in our check of the file name length.
      // TODO: Do we need this?
      // $validators['file_validate_name_length'] = array();
      // Call the validation functions specified by this function's caller.
      $errors = embridge_asset_validate($asset, $validators);

      // Check for errors.
      if (!empty($errors)) {
        $message = array(
          'error' => array(
            '#markup' => t('The specified file %name could not be uploaded.', array('%name' => $asset->getFilename())),
          ),
          'item_list' => array(
            '#theme' => 'item_list',
            '#items' => $errors,
          ),
        );
        // @todo Add support for render arrays in drupal_set_message()? See
        // https://www.drupal.org/node/2505497.
        drupal_set_message(\Drupal::service('renderer')->renderPlain($message), 'error');
        $assets[$i] = FALSE;
        continue;
      }

      // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary
      // directory. This overcomes open_basedir restrictions for future file
      // operations.
      $asset->setSourcePath($asset_destination);
      if (!drupal_move_uploaded_file($file_info->getRealPath(), $asset->getSourcePath())) {
        drupal_set_message(t('File upload error. Could not move uploaded file.'), 'error');
        \Drupal::logger('file')->notice('Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $asset->getFilename(), '%destination' => $asset->getSourcePath()));
        $assets[$i] = FALSE;
        continue;
      }

      // Set the permissions on the new file.
      drupal_chmod($asset->getSourcePath());

      // If we are replacing an existing file re-use its database record.
      // @todo Do not create a new entity in order to update it. See
      // https://www.drupal.org/node/2241865.
      if ($replace == FILE_EXISTS_REPLACE) {
        $existing_files = entity_load_multiple_by_properties('embridge_asset_entity', array('uri' => $asset->getSourcePath()));
        if (count($existing_files)) {
          $existing = reset($existing_files);
          $asset->setOriginalId($existing->id());
        }
      }

      /** @var \Drupal\embridge\EnterMediaDbClientInterface $embridge_client */
      $embridge_client = \Drupal::getContainer()->get('embridge.client');

      try {
        $embridge_client->upload($asset);
      }
      catch (\Exception $e) {
        $message = $e->getMessage();
        drupal_set_message(t('Uploading the file "%file" to EnterMedia failed with the message "%message".', array('%file' => $asset->getFilename(), '%message' => $message)), 'error');
        $assets[$i] = FALSE;
        continue;
      }

      // If we made it this far it's safe to record this file in the database.
      $asset->save();
      $assets[$i] = $asset;
    }

    // Add files to the cache.
    $upload_cache[$form_field_name] = $assets;

    return isset($delta) ? $assets[$delta] : $assets;
  }

}
