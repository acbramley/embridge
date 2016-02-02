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
    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished embridge asset entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published embridge asset entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit embridge asset entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete embridge asset entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add embridge asset entity entities');
  }

}
