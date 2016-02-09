<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeSearchForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embridge\EnterMediaDbClient;

/**
 * Class EmbridgeSearchForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeSearchForm extends FormBase {

  /**
   * Drupal\embridge\EnterMediaDbClient definition.
   *
   * @var \Drupal\embridge\EnterMediaDbClient
   */
  protected $client;

  public function __construct(EnterMediaDbClient $embridge_client) {
    $this->client = $embridge_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('embridge.client')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'embridge_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // For access in the AJAX request.
    $form['client'] = [
      '#type' => 'value',
      '#value' => $this->client,
    ];

    $form['filename'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search by filename'),
      '#description' => $this->t('Filter the search by filename'),
      '#size' => 20,
      '#default_value' => $form_state->get('filename'),
    );

    $operation_options = [
      'matches' => $this->t('Matches'),
      'startswith' => $this->t('Starts with')
    ];
    $form['filename_op'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => $operation_options,
      '#description' => $this->t('Operation to apply to filename search'),
      '#default_value' => $form_state->get('filename_op'),
    );
    $ajax_wrapper_id = 'embridge-results-wrapper';
    $form['results_wrapper_id'] = [
      '#type' => 'value',
      '#value' => $ajax_wrapper_id,
    ];
    $form['search_results'] = [
      '#markup' => '<div id="' . $ajax_wrapper_id . '"></div>'
    ];

    $ajax_settings = [
      'callback' => [get_called_class(), 'searchAjaxCallback'],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
    $form['search'] = [
      '#type' => 'submit',
      '#ajax' => $ajax_settings,
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public static function searchAjaxCallback(array $form, FormStateInterface $form_state) {
    $filters = [
      [
        'field' => 'name',
        'operator' => $form_state->getValue('filename_op'),
        'value' => $form_state->getValue('filename'),
      ],
    ];

    /** @var EnterMediaDbClient $client */
    $client = $form_state->getValue('client');
    $num_per_page = 20;
    $response = $client->search(1, $num_per_page, $filters);
    pager_default_initialize($response['response']['totalhits'], $num_per_page);

    $render = [
      [
        '#markup' => '<div id="' . $form_state->getValue('results_wrapper_id') . '">'
      ],
      [
        '#theme' => 'embridge_search_results',
        '#results' => $response['results'],
      ],
      [
        '#type' => 'pager'
      ],
      [
        '#markup' => '</div>',
      ]
    ];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(NULL, $render));
    return $response;
  }

}
