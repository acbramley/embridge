<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldFormatter\GenericEmbridgeAsset.
 */

namespace Drupal\embridge\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'embridge_default' formatter.
 *
 * @FieldFormatter(
 *   id = "embridge_default",
 *   label = @Translation("Generic embridge asset"),
 *   field_types = {
 *     "embridge_asset_item"
 *   }
 * )
 */
class GenericEmbridgeAssetFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_to' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $link_types = array(
      'content' => t('Content'),
    );
    $element['link_to'] = array(
      '#title' => t('Link to:'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('link_to'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $link_types = array(
      '' => t('Linked to nothing'),
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $link_setting = $this->getSetting('link_to');
    if (isset($link_types[$link_setting])) {
      $summary[] = $link_types[$link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $link_setting = $this->getSetting('link_to');
    $entity = $items->getEntity();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $asset) {
      $item = $asset->_referringItem;
      $elements[$delta] = array(
        '#theme' => 'embridge_file_link',
        '#asset' => $asset,
        '#description' => $item->description,
        '#entity' => $entity,
        '#link_to' => $link_setting,
        '#cache' => array(
          'tags' => $asset->getCacheTags(),
        ),
      );
    }

    return $elements;
  }

}
