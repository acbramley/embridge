<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Field\FieldWidget\EmbridgeAssetWidget.
 */

namespace Drupal\embridge\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
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

}
