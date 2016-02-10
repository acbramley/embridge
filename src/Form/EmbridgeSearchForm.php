<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeSearchForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\embridge\EnterMediaAssetHelper;
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

  /**
   * Our asset helper.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelper
   */
  protected $assetHelper;

  public function __construct(EnterMediaDbClient $embridge_client, EnterMediaAssetHelper $asset_helper) {
    $this->client = $embridge_client;
    $this->assetHelper = $asset_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('embridge.client'),
      $container->get('embridge.asset_helper')
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
    $ajax_wrapper_id = 'embridge-results-wrapper';
    $form['#prefix'] =  '<div id="' . $ajax_wrapper_id . '">';
    $form['#sufix'] = '</div>';

    // For access in the AJAX request.
    $form['client'] = [
      '#type' => 'value',
      '#value' => $this->client,
    ];
    $form['asset_helper'] = [
      '#type' => 'value',
      '#value' => $this->assetHelper,
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
    $table = [
      '#type' => 'tableselect',
      '#header' => [$this->t('File')],
      '#empty' => $this->t('No search results.'),
    ];

    $form['search_results'] = [
      'table' => $table,
      'pager' => ['#type' => 'pager'],
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select'),
      '#tableselect' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public static function searchAjaxCallback(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $filters = [
      [
        'field' => 'name',
        'operator' => $form_state->getValue('filename_op'),
        'value' => $form_state->getValue('filename'),
      ],
    ];

    /** @var EnterMediaDbClient $client */
    $client = $form_state->getValue('client');
    /** @var EnterMediaAssetHelper $asset_helper */
    $asset_helper = $form_state->getValue('asset_helper');

    $num_per_page = 20;
    $search_response = $client->search(1, $num_per_page, $filters);

    $form['search_results']['table']['#options'] = [];
    foreach($search_response['results'] as $result) {
      $asset = $asset_helper->searchResultToAsset($result);
      $form['search_results']['table']['#options'][$asset->getAssetId()] = [$asset->getFilename()];
    }

    // Manually call processTableSelect to generate the checkboxes again.
    Tableselect::processTableselect($form['search_results']['table'], $form_state, $form);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

}
