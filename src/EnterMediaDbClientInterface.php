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
   * Uploads a file to the EMDB instance, updates properties on the asset.
   *
   * @param EmbridgeAssetEntityInterface $asset
   *   An asset entity with a file reference to send to the server.
   *
   * @return EmbridgeAssetEntityInterface
   *   The updated and saved asset.
   */
  public function upload(EmbridgeAssetEntityInterface $asset);

  /**
   * Searches the EMDB instance for assets, given a set of filters.
   *
   * @param int $page
   *   The page to fetch.
   * @param int $number_of_items
   *   The number of items to fetch per page.
   * @param array $filters
   *   An array of filters to apply to the query. Each filter is an array with keys:
   *    - field: the field to apply the filter to
   *    - operator: the operator to apply
   *    - value: the value of the operator.
   *
   * @return array
   *   A decoded JSON response.
   */
  public function search($page = 1, $number_of_items = 20, $filters = []);

}
