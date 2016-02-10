<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeSearchForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
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

//    if (isset($form_state->getUserInput()['dialogOptions']['form_element'])) {
//      $embridge_file_element = $form_state->getUserInput()['dialogOptions']['form_element'];
//      $form_state->set('embridge_file_element', $embridge_file_element);
//      $form_state->setCached(TRUE);
//    }
//    else {
//      $embridge_file_element = $form_state->get('embridge_file_element') ?: [];
//    }

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
      'startswith' => $this->t('Starts with'),
      'matches' => $this->t('Matches'),
    ];
    $form['filename_op'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => $operation_options,
      '#description' => $this->t('Operation to apply to filename search'),
      '#default_value' => $form_state->get('filename_op'),
    );

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

    $table = [
      '#type' => 'tableselect',
      '#header' => [$this->t('File')],
      '#empty' => $this->t('No search results.'),
      '#default_value' => $form_state->get('search_results_table')
    ];

    $form['search_results'] = [
      'search_results_table' => $table,
      'pager' => ['#type' => 'pager'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      //'#tableselect' => TRUE,
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button != 'search') {
      if ($form_state->getErrors()) {

      }
      else {
        $response->addCommand(new CloseModalDialogCommand());
      }

      return $response;
    }
  }

  /**
   * Searches the EMDB instance using the user entered filters.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
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

    $form['search_results']['search_results_table']['#options'] = [];
    foreach($search_response['results'] as $result) {
      $asset = $asset_helper->searchResultToAsset($result);
      $form['search_results']['search_results_table']['#options'][$asset->getAssetId()] = [$asset->getFilename()];
    }

    // Manually call processTableSelect to generate the checkboxes again.
    Tableselect::processTableselect($form['search_results']['search_results_table'], $form_state, $form);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

}
