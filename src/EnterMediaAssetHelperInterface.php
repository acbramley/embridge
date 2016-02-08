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
  public function getAssetConversionUrl(
    EmbridgeAssetEntityInterface $asset,
    $conversion
  );
}