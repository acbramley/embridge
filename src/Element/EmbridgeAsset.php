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
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\embridge\EnterMediaDbClientInterface;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    $catalog_id = $element['#catalog_id'];
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
      if (!$files = self::saveUpload($upload_name, $catalog_id, $element['#upload_validators'], $destination)) {
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

  /**
   * Saves file uploads to a new location.
   *
   * The files will be added to the {file_managed} table as temporary files.
   * Temporary files are periodically cleaned. Use the 'file.usage' service to
   * register the usage of the file which will automatically mark it as permanent.
   *
   * @param string $form_field_name
   *   A string that is the associative array key of the upload form element in
   *   the form array.
   * @param string $catalog_id
   *   EnterMedia Catalog ID.
   * @param array $validators
   *   An optional, associative array of callback functions used to validate the
   *   file. See file_validate() for a full discussion of the array format.
   *   If no extension validator is provided it will default to a limited safe
   *   list of extensions which is as follows: "jpg jpeg gif png txt
   *   doc xls pdf ppt pps odt ods odp". To allow all extensions you must
   *   explicitly set the 'file_validate_extensions' validator to an empty array
   *   (Beware: this is not safe and should only be allowed for trusted users, if
   *   at all).
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
   * @return array
   *   Function returns array of files or a single file object if $delta
   *   != NULL. Each file object contains the file information if the
   *   upload succeeded or FALSE in the event of an error. Function
   *   returns NULL if no file was uploaded.
   *
   *   The documentation for the "File interface" group, which you can find under
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
    /** @var UploadedFile[] $uploaded_files */
    $uploaded_files = $file_upload;
    if (!is_array($file_upload)) {
      $uploaded_files = array($file_upload);
    }

    $files = array();
    foreach ($uploaded_files as $i => $file_info) {
      // Check for file upload errors and return FALSE for this file if a lower
      // level system error occurred. For a complete list of errors:
      // See http://php.net/manual/features.file-upload.errors.php.
      switch ($file_info->getError()) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          drupal_set_message(t('The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $file_info->getFilename(), '%maxsize' => format_size(file_upload_max_size()))), 'error');
          $files[$i] = FALSE;
          continue;

        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          drupal_set_message(t('The file %file could not be saved because the upload did not complete.', array('%file' => $file_info->getFilename())), 'error');
          $files[$i] = FALSE;
          continue;

        case UPLOAD_ERR_OK:
          // Final check that this is a valid upload, if it isn't, use the
          // default error handler.
          if (is_uploaded_file($file_info->getRealPath())) {
            break;
          }

        // Unknown error
        default:
          drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => $file_info->getFilename())), 'error');
          $files[$i] = FALSE;
          continue;

      }
      // Begin building file entity.
      $values = array(
        'uid' => $user->id(),
        'filename' => $file_info->getClientOriginalName(),
        'uri' => $file_info->getRealPath(),
        'filesize' => $file_info->getSize(),
      );
      $values['filemime'] = \Drupal::service('file.mime_type.guesser')->guess($values['filename']);

      // Create our Embridge Entity.
      /** @var EmbridgeAssetEntityInterface $file */
      $file = EmbridgeAssetEntity::create($values);

      $extensions = '';
      if (isset($validators['file_validate_extensions'])) {
        if (isset($validators['file_validate_extensions'][0])) {
          // Build the list of non-munged extensions if the caller provided them.
          $extensions = $validators['file_validate_extensions'][0];
        }
        else {
          // If 'file_validate_extensions' is set and the list is empty then the
          // caller wants to allow any extension. In this case we have to remove the
          // validator or else it will reject all extensions.
          unset($validators['file_validate_extensions']);
        }
      }
      else {
        // No validator was provided, so add one using the default list.
        // Build a default non-munged safe list for file_munge_filename().
        $extensions = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp';
        $validators['file_validate_extensions'] = array();
        $validators['file_validate_extensions'][0] = $extensions;
      }

      if (!empty($extensions)) {
        // Munge the filename to protect against possible malicious extension
        // hiding within an unknown file type (ie: filename.html.foo).
        $file->setFilename(file_munge_filename($file->getFilename(), $extensions));
      }

      // Rename potentially executable files, to help prevent exploits (i.e. will
      // rename filename.php.foo and filename.php to filename.php.foo.txt and
      // filename.php.txt, respectively). Don't rename if 'allow_insecure_uploads'
      // evaluates to TRUE.
      if (!\Drupal::config('system.file')->get('allow_insecure_uploads') && preg_match('/\.(php|pl|py|cgi|asp|js)(\.|$)/i', $file->getFilename()) && (substr($file->getFilename(), -4) != '.txt')) {
        $file->setMimeType('text/plain');
        // The destination filename will also later be used to create the URI.
        $file->setFilename($file->getFilename() . '.txt');
        // The .txt extension may not be in the allowed list of extensions. We have
        // to add it here or else the file upload will fail.
        if (!empty($extensions)) {
          $validators['file_validate_extensions'][0] .= ' txt';
          drupal_set_message(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $file->getFilename())));
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
        $files[$i] = FALSE;
        continue;
      }

      $file->source = $form_field_name;
      // A file URI may already have a trailing slash or look like "public://".
      if (substr($destination_dir, -1) != '/') {
        $destination_dir .= '/';
      }
      $file_destination = file_destination($destination_dir . $file->getFilename(), $replace);
      // If file_destination() returns FALSE then $replace === FILE_EXISTS_ERROR and
      // there's an existing file so we need to bail.
      if ($file_destination === FALSE) {
        drupal_set_message(t('The file %source could not be uploaded because a file by that name already exists in the destination %directory.', array('%source' => $form_field_name, '%directory' => $destination_dir)), 'error');
        $files[$i] = FALSE;
        continue;
      }

      // Add in our check of the file name length.
      $validators['file_validate_name_length'] = array();

      // Call the validation functions specified by this function's caller.
      // TODO: Implement this for Embridge Assets.
      // $errors = file_validate($file, $validators);

      // Check for errors.
      if (!empty($errors)) {
        $message = array(
          'error' => array(
            '#markup' => t('The specified file %name could not be uploaded.', array('%name' => $file->getFilename())),
          ),
          'item_list' => array(
            '#theme' => 'item_list',
            '#items' => $errors,
          ),
        );
        // @todo Add support for render arrays in drupal_set_message()? See
        //  https://www.drupal.org/node/2505497.
        drupal_set_message(\Drupal::service('renderer')->renderPlain($message), 'error');
        $files[$i] = FALSE;
        continue;
      }

      // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary
      // directory. This overcomes open_basedir restrictions for future file
      // operations.
      $file->setSourcePath($file_destination);
      if (!drupal_move_uploaded_file($file_info->getRealPath(), $file->getSourcePath())) {
        drupal_set_message(t('File upload error. Could not move uploaded file.'), 'error');
        \Drupal::logger('file')->notice('Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $file->getFilename(), '%destination' => $file->getFileUri()));
        $files[$i] = FALSE;
        continue;
      }

      // Set the permissions on the new file.
      drupal_chmod($file->getSourcePath());

      // If we are replacing an existing file re-use its database record.
      // @todo Do not create a new entity in order to update it. See
      //   https://www.drupal.org/node/2241865.
      if ($replace == FILE_EXISTS_REPLACE) {
        $existing_files = entity_load_multiple_by_properties('embridge_asset_entity', array('uri' => $file->getSourcePath()));
        if (count($existing_files)) {
          $existing = reset($existing_files);
          $file->fid = $existing->id();
          $file->setOriginalId($existing->id());
        }
      }

      /** @var EnterMediaDbClientInterface $embridge_client */
      $embridge_client = \Drupal::getContainer()->get('embridge.client');
      $embridge_client->upload($file);

      // If we made it this far it's safe to record this file in the database.
      $file->save();
      $files[$i] = $file;
    }

    // Add files to the cache.
    $upload_cache[$form_field_name] = $files;

    return isset($delta) ? $files[$delta] : $files;
  }

}