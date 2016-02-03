<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeAssetEntityAccessControlHandler.
 */

namespace Drupal\embridge;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Embridge asset entity entity.
 *
 * @see \Drupal\embridge\Entity\EmbridgeAssetEntity.
 */
class EmbridgeAssetEntityAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer embridge asset entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer embridge asset entities');
  }

}
