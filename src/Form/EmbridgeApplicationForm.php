<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeApplicationForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\embridge\Entity\EmbridgeApplication;

/**
 * Class EmbridgeApplicationForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeApplicationForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var EmbridgeApplication $embridge_application */
    $embridge_application = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $embridge_application->label(),
      '#description' => $this->t("Label for the EMBridge Application."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $embridge_application->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\embridge\Entity\EmbridgeApplication::load',
      ),
      '#disabled' => !$embridge_application->isNew(),
    );

    $form['applicationId'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $embridge_application->getApplicationId(),
      '#description' => $this->t("The Application ID from EnterMedia DB."),
      '#required' => TRUE,
    );

    $form['conversions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Conversions'),
      '#default_value' => $embridge_application->getConversions(),
      '#width' => 60,
      '#description' => $this->t("A newline separated list of conversions. These will be available as formatter options when a field is configured to use this Application."),
      '#required' => TRUE,
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var EmbridgeApplication $embridge_application */
    $embridge_application = $this->entity;
    $status = $embridge_application->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label EMBridge Application.', [
          '%label' => $embridge_application->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label EMBridge Application.', [
          '%label' => $embridge_application->label(),
        ]));
    }
    $form_state->setRedirectUrl($embridge_application->urlInfo('collection'));
  }

}
