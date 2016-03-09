<?php
/**
 * @file
 * File of testing functions.
 */

namespace Drupal\embridge_ckeditor\Form;

// Mock time() for our namespace.
use Drupal\Component\Utility\Bytes;

if (!function_exists('\Drupal\embridge_ckeditor\Form\file_upload_max_size')) {

  /**
   * Mock instance of the file_upload_max_size() function.
   *
   * @return int
   *   The same size always.
   */
  function file_upload_max_size() {
    return Bytes::toInt('10 MB');
  }
}
