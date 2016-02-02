<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaDbClientInterface.
 */

namespace Drupal\embridge;

use Drupal\embridge\Entity\EmbridgeAssetEntity;

/**
 * Class EnterMediaDbClient.
 *
 * @package Drupal\embridge
 */
interface EnterMediaDbClientInterface {

  /**
   * Initialises a Request object with the configuration.
   *
   * @param string $path
   *   An optional path relative to the base uri in configuration.
   *
   * @return \GuzzleHttp\Psr7\Request
   */
  public function initRequest($path = '');

  /**
   * Logs into the EMDB instance.
   *
   * @return bool
   *   Whether the login was successful or not.
   *
   * @throws \Exception
   *   When the login fails in various ways.
   */
  public function login();

  /**
   * Uploads a file to the EMDB instance.
   *
   * @return EmbridgeAssetEntityInterface[]|bool
   *  An array of asset entities that were saved, or FALSE if the upload failed.
   */
  public function upload();
}
