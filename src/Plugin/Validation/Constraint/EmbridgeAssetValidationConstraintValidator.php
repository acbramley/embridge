<?php

/**
 * @file
 * Contains \Drupal\embridge\Plugin\Validation\Constraint\EmbridgeAssetValidationConstraintValidator.
 */

namespace Drupal\embridge\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that a embridge asset file referenced in a embridge file field is valid.
 */
class EmbridgeAssetValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Get the file to execute validators.
    $asset = $value->get('entity')->getTarget()->getValue();
    // Get the validators.
    $validators = $value->getUploadValidators();
    // Checks that a file meets the criteria specified by the validators.
    if ($errors = embridge_asset_validate($asset, $validators)) {
      foreach ($errors as $error) {
        $this->context->addViolation($error);
      }
    }
  }

}
