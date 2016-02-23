<?php
/**
 * @file
 * File of testing functions.
 */

namespace Drupal\embridge {

  define('MOCK_TIMESTAMP', 1456192161);

  // Mock time() for our namespace.
  if (!function_exists('\Drupal\embridge\time')) {

    /**
     * Mock instance of the time() function.
     *
     * @return int
     *   The same timestamp always.
     */
    function time() {
      return MOCK_TIMESTAMP;
    }
  }
}