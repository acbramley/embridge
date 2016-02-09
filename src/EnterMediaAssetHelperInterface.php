<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelperInterface.
 */

namespace Drupal\embridge;

/**
 * Interface EnterMediaAssetHelperInterface.
 *
 * @package Drupal\embridge
 */
interface EnterMediaAssetHelperInterface {
  /**
   * Returns a url for an Embridge Asset entity using a conversion.
   *
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The embridge asset entity
   * @param string $conversion
   *   The conversion to get the url for.
   *
   * @return string
   */
  public function getAssetConversionUrl(EmbridgeAssetEntityInterface $asset, $conversion);

  /**
   * Converts a search result to a Embridge Asset Entity.
   *
   * @param array $result
   *   A result from the search results.
   *
   * @return EmbridgeAssetEntityInterface
   *   The populated and saved entity.
   */
  public function searchResultToAsset($result);

}