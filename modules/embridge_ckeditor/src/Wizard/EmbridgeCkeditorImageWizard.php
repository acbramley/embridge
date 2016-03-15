<?php

/**
 * @file
 * Contains \Drupal\ctools_wizard_test\Wizard\WizardTest.
 */

namespace Drupal\embridge_ckeditor\Wizard;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Ajax\OpenModalWizardCommand;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;

/**
 * Class EmbridgeCkeditorImageWizard.
 *
 * @package Drupal\embridge_ckeditor\Wizard
 */
class EmbridgeCkeditorImageWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Wizard Information');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Wizard Test Name');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'embridge_ckeditor.image.wizard.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $params = parent::getPreviousParameters($cached_values);
    $params['js'] = 'ajax';

    // Merge route parameters into the cached values.
    $attributes = \Drupal::request()->attributes->all()['_raw_variables']->all();
    $params = array_merge($attributes, $params);

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    $params = parent::getNextParameters($cached_values);
    $params['js'] = 'ajax';

    // Merge route parameters into the cached values.
    $attributes = \Drupal::request()->attributes->all()['_raw_variables']->all();
    $params = array_merge($attributes, $params);

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    return array(
      'one' => [
        'form' => 'Drupal\embridge\Form\EmbridgeSearchForm',
        'title' => $this->t('Search assets'),
        'submit' => ['::stepOneSubmit'],
      ],
      'two' => [
        'form' => 'Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog',
        'title' => $this->t('Add Image'),
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL, FieldDefinitionInterface $field_config = NULL) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    // Get the current form operation.
    $operation = $this->getOperation($cached_values);
    $form = $this->customizeForm($form, $form_state);
    /* @var \Drupal\Core\Form\FormInterface $form_class */
    $form_class = $this->classResolver->getInstanceFromDefinition($operation['form']);

    // Build the form, pass arguments in based on which form we are rendering.
    $args = $form_state->getBuildInfo()['args'];
    if ($operation['form'] == 'Drupal\embridge\Form\EmbridgeSearchForm') {
      $form = $form_class->buildForm($form, $form_state, $args[1]);
    }
    else {
      $form = $form_class->buildForm($form, $form_state, $args[0]);
    }

    if (isset($operation['title'])) {
      $form['#title'] = $operation['title'];
    }
    $form['actions'] = $this->actions($form_class, $form_state);

    if ($operation['form'] != 'Drupal\embridge\Form\EmbridgeSearchForm') {
      $form['actions']['submit']['#ajax']['callback'] = [
        $form_class,
        'ajaxSave',
      ];
    }

    return $form;
  }

  /**
   * Submission callback for the first step.
   */
  public function stepOneSubmit($form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->step = 'two';
    $parameters = $this->getNextParameters($cached_values);

    $asset = EmbridgeAssetEntity::load($form_state->getValue('result_chosen'));

    $image_element = [
      'src' => \Drupal::getContainer()->get('embridge.asset_helper')->getAssetConversionUrl($asset, 'emshare', 'thumb'),
      'data-entity-uuid' => $asset->uuid(),
      'data-entity-type' => 'embridge_asset_entity',
      'alt' => '',
      'width' => '',
      'height' => '',
    ];
    $parameters['image_element'] = $image_element;
    $form_state->setTemporaryValue('wizard', $parameters);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $parameters = $this->getNextParameters($cached_values);
    $parameters['filter_format'] = FilterFormat::load($parameters['filter_format']);
    $parameters['field_config'] = FieldConfig::load($parameters['field_config']);
    $response->addCommand(new OpenModalWizardCommand($this, $this->getTempstoreId(), $parameters));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxPrevious(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $parameters = $this->getPreviousParameters($cached_values);
    $parameters['filter_format'] = FilterFormat::load($parameters['filter_format']);
    $parameters['field_config'] = FieldConfig::load($parameters['field_config']);
    $response->addCommand(new OpenModalWizardCommand($this, $this->getTempstoreId(), $parameters));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    parent::finish($form, $form_state);
  }

}
