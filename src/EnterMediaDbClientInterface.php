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
   */
  public function login();
}
