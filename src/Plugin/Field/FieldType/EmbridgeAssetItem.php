<?php
/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldType\EmbridgeAssetItem
 */

namespace Drupal\embridge\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "embridge_asset_item",
 *   label = @Translation("Embridge asset item"),
 *   description = @Translation("This field stores the ID of an EnterMedia asset as an integer value"),
 *   category = @Translation("Reference"),
 *   default_widget = "embridge_asset_widget",
 *   default_formatter = "embridge_default",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "EmbridgeAssetValidation" = {}}
 * )
 */
class EmbridgeAssetItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => 'embridge_asset_entity',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'catalog_id' => '',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'target_id' => array(
          'description' => 'The ID of the EM asset entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ),
        'display' => array(
          'description' => 'Flag to control whether this file should be displayed when viewing content.',
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 1,
        ),
        'description' => array(
          'description' => 'A description of the file.',
          'type' => 'text',
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
      'foreign keys' => array(
        'target_id' => array(
          'table' => 'embridge_asset_entity',
          'columns' => array('target_id' => 'id'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();

    $element['catalog_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Catalog Id'),
      '#default_value' => $settings['catalog_id'],
      '#description' => t('Select the catalog for this field.'),
      '#weight' => 6,
    );

    return $element + parent::fieldSettingsForm($form, $form_state);
  }
}