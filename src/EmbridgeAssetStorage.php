<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeAssetStorage.
 */

namespace Drupal\embridge;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * File storage for files.
 */
class EmbridgeAssetStorage extends SqlContentEntityStorage implements EmbridgeAssetStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function spaceUsed($uid = NULL, $status = FILE_STATUS_PERMANENT) {
    $query = $this->database->select($this->entityType->getBaseTable(), 'ea')
      ->condition('ea.status', $status);
    $query->addExpression('SUM(ea.filesize)', 'filesize');
    if (isset($uid)) {
      $query->condition('ea.uid', $uid);
    }
    return $query->execute()->fetchField();
  }

}
