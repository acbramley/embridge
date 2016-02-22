<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeAssetValidatorInterface.
 */

namespace Drupal\embridge;

/**
 * Interface EmbridgeAssetValidatorInterface.
 *
 * @package Drupal\embridge
 */
interface EmbridgeAssetValidatorInterface {


  /**
   * Checks that a file meets the criteria specified by the validators.
   *
   * After executing the validator callbacks specified
   * hook_embridge_asset_validate() will also be called to allow other modules
   * to report errors about the file.
   *
   * @param EmbridgeAssetEntityInterface $asset
   *   A file entity.
   * @param array $validators
   *   An optional, associative array of callback functions used to validate the
   *   file. The keys are function names and the values arrays of callback
   *   parameters which will be passed in after the file entity. The
   *   functions should return an array of error messages; an empty array
   *   indicates that the file passed validation. The functions will be called
   *   in the order specified.
   *
   * @return array
   *   An array containing validation error messages.
   *
   * @see hook_embridge_asset_validate()
   */
  public function validate(EmbridgeAssetEntityInterface $asset, $validators = array());

  /**
   * Checks that the filename ends with an allowed extension.
   *
   * @param EmbridgeAssetEntityInterface $asset
   *   An Embridge asset entity.
   * @param string $extensions
   *   A string with a space separated list of allowed extensions.
   *
   * @return array
   *   An array. If the file extension is not allowed, it will contain an error
   *   message.
   */
  public function validateFileExtensions(EmbridgeAssetEntityInterface $asset, $extensions);

  /**
   * Checks that the file's size is below certain limits.
   *
   * @param EmbridgeAssetEntityInterface $asset
   *   An embridge asset entity.
   * @param int $file_limit
   *   An integer specifying the maximum file size in bytes. Zero indicates that
   *   no limit should be enforced.
   * @param int $user_limit
   *   An integer specifying the maximum number of bytes the user is allowed.
   *   Zero indicates that no limit should be enforced.
   *
   * @return array
   *   An array. If the file size exceeds limits, it will contain an error
   *   message.
   *
   * @see hook_file_validate()
   */
  public function validateFileSize(EmbridgeAssetEntityInterface $asset, $file_limit = 0, $user_limit = 0);

}
