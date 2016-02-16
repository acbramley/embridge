<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldType\FileFieldItemList.
 */

namespace Drupal\embridge\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity file field.
 */
class EmbridgeAssetItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface[] $assets */
    $assets = $this->referencedEntities();

    // Set all assets permanent on form submission.
    foreach ($assets as $i => $asset) {
      if ($asset->isTemporary()) {
        $asset->setPermanent();
        $asset->save();
      }
    }
  }

}
