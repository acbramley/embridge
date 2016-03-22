<?php

/**
 * @file
 * Contains Drupal\embridge\Form\EmbridgeSettingsForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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

  /**
   * EmbridgeSettingsForm constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param EnterMediaDbClientInterface $client
   *   The EMDB client service.
   */
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

    $form['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection status'),
      'message' => [
        '#markup' => '<div class="connection-test-message"></div>',
      ],
      'test' => [
        '#type' => 'button',
        '#value' => $this->t('Test Connection'),
        '#description' => $this->t('Test a connection with the latest saved configuration.'),
        '#ajax' => [
          'callback' => [$this, 'testConnectionAjax'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ],
    ];
    $form['connection'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Connection'),
      '#collapsible' => TRUE,
    );
    $form['connection']['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Server uri'),
      '#description' => $this->t('EnterMedia Hostname (e.g. http://entermedia.databasepublish.com).'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('uri'),
      '#required' => TRUE,
    ];
    $form['connection']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Login for EnterMedia service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('username'),
      '#required' => TRUE,
    ];
    $form['connection']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password for EnterMedia service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('password'),
      '#required' => TRUE,
    ];
    $form['connection']['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('Timeout value for API calls, in seconds.'),
      '#maxlength' => 8,
      '#size' => 8,
      '#default_value' => $config->get('timeout'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to validate the email field.
   */
  public function testConnectionAjax(array &$form, FormStateInterface $form_state) {
    $valid = FALSE;
    try {
      $valid = $this->client->login();
    }
    catch (\Exception $e) {

    }

    $response = new AjaxResponse();
    if ($valid) {
      $css = ['border' => '1px solid green'];
      $message = $this->t('Connection ok.');
    }
    else {
      $css = ['border' => '1px solid red'];
      $message = $this->t('Connection failed.');
    }
    $response->addCommand(new CssCommand('#edit-connection', $css));
    $response->addCommand(new HtmlCommand('.connection-test-message', $message));
    return $response;
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
      ->set('uri', $form_state->getValue('uri'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->save();
  }

}
