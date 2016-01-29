<?php

/**
 * @file
 * Contains Drupal\embridge\Form\EmbridgeSettingsForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Class EmbridgeSettingsForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeSettingsForm extends ConfigFormBase {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $http_client;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      Client $http_client
    ) {
    parent::__construct($config_factory);
        $this->http_client = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('http_client')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'embridge.embridgesettings',
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
    $config = $this->config('embridge.embridgesettings');
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server url'),
      '#description' => $this->t('EnterMedia Hostname (e.g. http://entermedia.databasepublish.com).'),
      '#maxlength' => 255,
      '#size' => 100,
      '#default_value' => $config->get('url'),
    );
    $form['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#description' => $this->t('EnterMedia server port (e.g. 8080).'),
      '#maxlength' => 64,
      '#size' => 7,
      '#default_value' => $config->get('port'),
    );
    $form['login_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Login details'),
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Login for EnterMedia service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('username'),
    );
    $form['password'] = array(
      '#type' => 'textfield',
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

    $this->config('embridge.embridgesettings')
      ->set('url', $form_state->getValue('url'))
      ->set('port', $form_state->getValue('port'))
      ->set('login_details', $form_state->getValue('login_details'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
