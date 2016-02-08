<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldFormatter\GenericEmbridgeAsset.
 */

namespace Drupal\embridge\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\embridge\EmbridgeApplicationInterface;
use Drupal\embridge\Entity\EmbridgeApplication;

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
    $application_id = $this->getFieldSetting('application_id');
    /** @var EmbridgeApplicationInterface $embridge_application */
    $embridge_application = EmbridgeApplication::load($application_id);
    $conversions = $embridge_application->getConversionsArray();
    $element['conversion'] = [
      '#title' => t('Conversion'),
      '#type' => 'select',
      '#options' => array_combine($conversions, $conversions),
      '#default_value' => $this->getSetting('conversion'),
    ];

    $element = $element + parent::settingsForm($form, $form_state);
    // Allow linking to the file when rendering an image with a conversion.
    $element['link_to']['#options']['file'] = t('Linked to file');

    return $element;
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
