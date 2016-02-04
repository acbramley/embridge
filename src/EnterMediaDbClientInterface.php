<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaDbClientInterface.
 */

namespace Drupal\embridge;

/**
 * Class EnterMediaDbClient.
 *
 * @package Drupal\embridge
 */
interface EnterMediaDbClientInterface {
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
   * @param EmbridgeAssetEntityInterface $asset
   *   An asset entity with a file reference to send to the server.
   *
   * @return array $response
   *   The formatted response from the server.
   */
  public function upload(EmbridgeAssetEntityInterface $asset);
}
