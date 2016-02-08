<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeApplicationInterface.
 */

namespace Drupal\embridge;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining EMBridge Application entities.
 */
interface EmbridgeApplicationInterface extends ConfigEntityInterface {

  /**
   * Return the Enter Media Application ID
   *
   * @return string
   */
  public function getApplicationId();

  /**
   * Returns a string of conversions, separated by newlines.
   *
   * @return string
   */
  public function getConversions();

  /**
   * Returns conversions in an array.
   *
   * @return array
   */
  public function getConversionsArray();
}
