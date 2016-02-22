<?php
/**
 * @file
 * Contains \Drupal\embridge\EmbridgeAssetValidator.
 */

namespace Drupal\embridge;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;

/**
 * Class EmbridgeAssetValidator.
 *
 * @package Drupal\embridge
 */
class EmbridgeAssetValidator implements EmbridgeAssetValidatorInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * EmbridgeAssetValidator constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   */
  public function __construct(ModuleHandler $module_handler, EntityTypeManager $entity_type_manager, AccountProxy $current_user) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(EmbridgeAssetEntityInterface $asset, $validators = array()) {
    // Call the validation functions specified by this function's caller.
    $errors = array();
    foreach ($validators as $function => $args) {
      if (method_exists($this, $function)) {
        array_unshift($args, $asset);
        $errors = array_merge($errors, call_user_func_array(array($this, $function), $args));
      }
    }

    // Let other modules perform validation on the new file.
    return array_merge($errors, $this->moduleHandler->invokeAll('embridge_asset_validate', array($asset)));
  }

  /**
   * {@inheritdoc}
   */
  public function validateFileExtensions(EmbridgeAssetEntityInterface $asset, $extensions) {
    $errors = array();

    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
    if (!preg_match($regex, $asset->getFilename())) {
      $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
    }
    return $errors;
  }


  /**
   * {@inheritdoc}
   */
  public function validateFileSize(EmbridgeAssetEntityInterface $asset, $file_limit = 0, $user_limit = 0) {
    $user = $this->currentUser;
    $errors = array();

    if ($file_limit && $asset->getSize() > $file_limit) {
      $errors[] = t('The file is %filesize exceeding the maximum file size of %maxsize.', array('%filesize' => format_size($asset->getSize()), '%maxsize' => format_size($file_limit)));
    }

    // Save a query by only calling spaceUsed() when a limit is provided.
    if ($user_limit && ($this->entityTypeManager->getStorage('embridge_asset')->spaceUsed($user->id()) + $asset->getSize()) > $user_limit) {
      $errors[] = t('The file is %filesize which would exceed your disk quota of %quota.', array('%filesize' => format_size($asset->getSize()), '%quota' => format_size($user_limit)));
    }

    return $errors;
  }

}
