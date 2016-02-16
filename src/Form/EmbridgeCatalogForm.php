<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeCatalogForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EmbridgeCatalogForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeCatalogForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\embridge\EmbridgeCatalogInterface $embridge_catalog */
    $embridge_catalog = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $embridge_catalog->label(),
      '#description' => $this->t("Label for the EMBridge Catalog."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $embridge_catalog->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\embridge\Entity\EmbridgeCatalog::load',
      ),
      '#disabled' => !$embridge_catalog->isNew(),
    );

    $form['applicationId'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $embridge_catalog->getApplicationId(),
      '#description' => $this->t("The application ID from EnterMedia DB."),
      '#required' => TRUE,
    );

    $form['conversions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Conversions'),
      '#default_value' => $embridge_catalog->getConversions(),
      '#width' => 60,
      '#description' => $this->t("A newline separated list of conversions. These will be available as formatter options when a field is configured to use this Catalog."),
      '#required' => TRUE,
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\embridge\EmbridgeCatalogInterface $embridge_catalog */
    $embridge_catalog = $this->entity;
    $status = $embridge_catalog->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label EMBridge Catalog.', [
          '%label' => $embridge_catalog->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label EMBridge Catalog.', [
          '%label' => $embridge_catalog->label(),
        ]));
    }
    $form_state->setRedirectUrl($embridge_catalog->urlInfo('collection'));
  }

}
