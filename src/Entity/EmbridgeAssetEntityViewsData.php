<?php

/**
 * @file
 * Contains \Drupal\embridge\Entity\EmbridgeAssetEntity.
 */

namespace Drupal\embridge\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Embridge asset entity entities.
 */
class EmbridgeAssetEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['embridge_asset_entity']['table']['base'] = array(
      'field' => 'aid',
      'title' => $this->t('Embridge asset entity'),
      'help' => $this->t('The Embridge asset ID.'),
    );

    return $data;
  }

}
