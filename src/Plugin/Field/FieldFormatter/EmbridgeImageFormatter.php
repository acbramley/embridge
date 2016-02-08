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
 *   id = "embridge_image",
 *   label = @Translation("EMBridge Image"),
 *   field_types = {
 *     "embridge_asset_item"
 *   }
 * )
 */
class EmbridgeImageFormatter extends GenericEmbridgeAssetFormatter {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'conversion' => 'thumb',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['conversion'] = [
      '#title' => t('Conversion'),
      '#type' => 'select',
      '#options' => ['thumb' => t('Thumbnail')],
      '#default_value' => $this->getSetting('conversion'),
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $conversion = $this->getSetting('conversion');
    if ($conversion) {
      $summary[] = t('Conversion: @style', ['@style' => $conversion]);
    }
    else {
      $summary[] = t('Original file');
    }

    $summary = array_merge($summary, parent::settingsSummary());

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $conversion = $this->getSetting('conversion');
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = 'embridge_image';
      $elements[$delta]['#conversion'] = $conversion;
    }

    return $elements;
  }

}
