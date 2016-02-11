<?php

/**
 * @file
 * Contains \Drupal\editor\Ajax\EditorDialogSave.
 */

namespace Drupal\embridge\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class EmbridgeSearchSave.
 *
 * @package Drupal\embridge\Ajax
 */
class EmbridgeSearchSave implements CommandInterface {

  /**
   * An array of values.
   *
   * @var string
   */
  protected $values;

  /**
   * Constructs a EditorDialogSave object.
   *
   * @param string $values
   *   The values that should be passed to the form constructor in Drupal.
   */
  public function __construct($values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return array(
      'command' => 'embridgeSearchDialogSave',
      'values' => $this->values,
    );
  }

}
