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
   * @return \GuzzleHttp\Psr7\Request
   */
  public function initRequest();

}
