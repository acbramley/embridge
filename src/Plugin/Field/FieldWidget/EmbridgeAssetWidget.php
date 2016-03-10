<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldWidget\EmbridgeAssetWidget.
 */

namespace Drupal\embridge\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\embridge\Element\EmbridgeAsset;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'embridge_asset_widget' widget.
 *
 * @FieldWidget(
 *   id = "embridge_asset_widget",
 *   label = @Translation("Embridge asset widget"),
 *   field_types = {
 *     "embridge_asset_item"
 *   }
 * )
 */
class EmbridgeAssetWidget extends FileWidget {

  /**
   * Element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Alter #upload_validators passed to #file_upload_description.
    $this->alterFileUploadHelpParameters($elements['#file_upload_description']);

    // TODO: Why do we need this?
    if ($this->isDefaultValueWidget($form_state) && !empty($elements[1])) {
      unset($elements[1]);
      $elements['#file_upload_delta'] = 0;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += array(
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    );

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = array(
      'fids' => array(),
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    );

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('embridge_asset');
    $element += array(
      '#type' => 'embridge_asset',
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#value_callback' => array(get_class($this), 'value'),
      '#process' => array_merge($element_info['#process'], array(array(get_class($this), 'process'))),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#field_name' => $this->fieldDefinition->getName(),
      '#field_config' => $this->fieldDefinition->id(),
      '#allow_search' => $field_settings['allow_search'],
      '#display_field' => (bool) $field_settings['display_field'],
      '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      '#cardinality' => $cardinality,
      '#catalog_id' => $field_settings['catalog_id'],
    );

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      $items[$delta]->fids = array($items[$delta]->target_id);
    }
    $element['#default_value'] = $items[$delta]->getValue() + $defaults;

    $default_fids = $element['#extended'] ? $element['#default_value']['fids'] : $element['#default_value'];
    if (empty($default_fids)) {
      $file_upload_help = array(
        '#theme' => 'file_upload_help',
        '#description' => $element['#description'],
        '#upload_validators' => $element['#upload_validators'],
        '#cardinality' => $cardinality,
      );

      $this->alterFileUploadHelpParameters($file_upload_help);

      $element['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = array(array(get_class($this), 'validateMultipleCount'));
      }
    }

    return $element;
  }


  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   *
   * @param array $element
   *   The element.
   * @param array|bool $input
   *   An array of input values, or FALSE.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return array|mixed|null
   *   An array values for the widget.
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      if (empty($input['display'])) {
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
    }

    // We depend on the managed file element to handle uploads.
    $return = EmbridgeAsset::valueCallback($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += array(
      'fids' => array(),
      'display' => 1,
      'description' => '',
    );

    return $return;
  }

  /**
   * Form element validation callback for upload element on file widget.
   *
   * Checks if user has uploaded more files than allowed.
   *
   * This validator is used only when cardinality not set to 1 or unlimited.
   *
   * @param array $element
   *   The element.
   * @param FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form.
   */
  public static function validateMultipleCount($element, FormStateInterface $form_state, $form) {
    $parents = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $parents);
    array_pop($parents);
    $current = count(Element::children(NestedArray::getValue($form, $parents))) - 1;

    $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($element['#entity_type']);
    $field_storage = $field_storage_definitions[$element['#field_name']];
    $uploaded = count($values['fids']);
    $count = $uploaded + $current;
    if ($count > $field_storage->getCardinality()) {
      $keep = $uploaded - $count + $field_storage->getCardinality();
      $removed_files = array_slice($values['fids'], $keep);
      $removed_names = array();
      foreach ($removed_files as $id) {
        /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $asset */
        $asset = EmbridgeAssetEntity::load($id);
        $removed_names[] = $asset->getFilename();
      }
      $args = array(
        '%field' => $field_storage->getName(),
        '@max' => $field_storage->getCardinality(),
        '@count' => $uploaded,
        '%list' => implode(', ', $removed_names),
      );
      $message = t('Field %field can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.', $args);
      drupal_set_message($message, 'warning');
      $values['fids'] = array_slice($values['fids'], 0, $keep);
      NestedArray::setValue($form_state->getValues(), $element['#parents'], $values);
    }
  }

  /**
   * We piggy back off file module's file_upload_help themeing.
   *
   * We need to alter the upload validator variables passed in to match what
   * it expects.
   *
   * @param array $file_upload_help
   *   The render array.
   */
  protected function alterFileUploadHelpParameters(&$file_upload_help) {
    if (empty($file_upload_help['#upload_validators'])) {
      return;
    }

    // Translate our custom upload validators to file ones so we can piggy
    // back off file_upload_help's themeing.
    foreach ($file_upload_help['#upload_validators'] as $func => $value) {
      if ($func == 'validateFileExtensions') {
        $file_upload_help['#upload_validators']['file_validate_extensions'] = $value;
      }
      elseif ($func == 'validateFileSize') {
        $file_upload_help['#upload_validators']['file_validate_size'] = $value;
      }
    }
  }

}
