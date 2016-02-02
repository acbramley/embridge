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
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
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
   * Form element validation callback for upload element on file widget. Checks
   * if user has uploaded more files than allowed.
   *
   * This validator is used only when cardinality not set to 1 or unlimited.
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
      foreach ($removed_files as $fid) {
        $file = File::load($fid);
        $removed_names[] = $file->getFilename();
      }
      $args = array('%field' => $field_storage->getName(), '@max' => $field_storage->getCardinality(), '@count' => $uploaded, '%list' => implode(', ', $removed_names));
      $message = t('Field %field can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.', $args);
      drupal_set_message($message, 'warning');
      $values['fids'] = array_slice($values['fids'], 0, $keep);
      NestedArray::setValue($form_state->getValues(), $element['#parents'], $values);
    }
  }

}
