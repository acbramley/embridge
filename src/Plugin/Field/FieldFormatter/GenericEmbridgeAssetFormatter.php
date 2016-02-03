<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldFormatter\GenericEmbridgeAsset.
 */

namespace Drupal\embridge\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
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
      'conversion' => '',
      'link_to_content' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'conversion' => [
        '#title' => t('Conversion'),
        '#type' => 'select',
        '#options' => [],
        '#default_value' => $this->getSetting('conversion'),
      ],
      'link_to_content' => [
        '#title' => t('Link to Content'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('link_to_content'),
      ]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $asset) {
      $elements[$delta] = array(
        '#theme' => 'embridge_file_link',
        '#asset' => $asset,
        '#cache' => array(
          'tags' => $asset->getCacheTags(),
        ),
      );
    }

    return $elements;
  }

}
