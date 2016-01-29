<?php

/**
 * @file
 * Contains Drupal\embridge\Form\EmbridgeSettingsForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\embridge\EnterMediaDbClient;
use Drupal\embridge\EnterMediaDbClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EmbridgeSettingsForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeSettingsForm extends ConfigFormBase {

  /**
   * Our EMDB client.
   *
   * @var EnterMediaDbClientInterface
   */
  protected $client;

  public function __construct(ConfigFactoryInterface $config_factory, EnterMediaDbClientInterface $client) {
    parent::__construct($config_factory);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('embridge.client')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'embridge.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'embridge_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('embridge.settings');
    $form['connection'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Connection'),
      '#collapsible' => TRUE,
    );
    $form['connection']['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server url'),
      '#description' => $this->t('EnterMedia Hostname (e.g. http://entermedia.databasepublish.com).'),
      '#maxlength' => 255,
      '#size' => 100,
      '#default_value' => $config->get('url'),
    );
    $form['connection']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#description' => $this->t('EnterMedia server port (e.g. 8080).'),
      '#maxlength' => 64,
      '#size' => 7,
      '#default_value' => $config->get('port'),
    );
    $form['connection']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Login for EnterMedia service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('username'),
    );
    $form['connection']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password for EnterMedia service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('password'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('embridge.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('port', $form_state->getValue('port'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
