<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeSearchForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\embridge\Ajax\EmbridgeSearchSave;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embridge\EnterMediaDbClient;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * EmbridgeSearchForm constructor.
   *
   * @param \Drupal\embridge\EnterMediaDbClient $embridge_client
   *   The embridge client.
   * @param \Drupal\embridge\EnterMediaAssetHelper $asset_helper
   *   A helper for asset entities.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EnterMediaDbClient $embridge_client, EnterMediaAssetHelper $asset_helper, EntityTypeManager $entity_type_manager) {
    $this->client = $embridge_client;
    $this->assetHelper = $asset_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('embridge.client'),
      $container->get('embridge.asset_helper'),
      $container->get('entity_type.manager')
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
  public function buildForm(array $form, FormStateInterface $form_state, $delta = 0) {
    $input = $form_state->getUserInput();

    $form['delta'] = [
      '#type' => 'value',
      '#value' => $delta,
    ];

    $form['filename'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search by filename'),
      '#description' => $this->t('Filter the search by filename'),
      '#size' => 20,
      '#default_value' => !empty($input['filename']) ? $input['filename'] : '',
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
      '#default_value' => !empty($input['filename_op']) ? $input['filename_op'] : '',
    );
    $ajax_wrapper_id = 'embridge-results-wrapper';

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

    $filters = [];
    if (!empty($input['filename_op'])) {
      $filters = [
        [
          'field' => 'name',
          'operator' => $input['filename_op'],
          'value' => $input['filename'],
        ],
      ];
    }

    $form['search_results'] = [
      '#theme' => 'embridge_search_results',
      '#results' => self::getSearchResults($this->client, $this->assetHelper, $filters)
    ];
    $form['result_chosen'] = [
      '#type' => 'hidden',
      '#value' =>  !empty($input['result_chosen']) ? $input['result_chosen'] : '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      //'#tableselect' => TRUE,
      '#ajax' => array(
        'callback' => '::submitFormSelection',
        'event' => 'click',
      ),
      // Hide the button.
      '#attributes' => array(
        'class' => array(
          'embridge-ajax-search-submit',
          'hidden-button',
        ),
      ),
    ];

    $form['#attached']['library'][] = 'embridge/embridge.lib';
    $form['#prefix'] =  '<div id="' . $ajax_wrapper_id . '">';
    $form['#sufix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button == 'submit') {
      $values = $form_state->getUserInput();
      $selected_result = $values['result_chosen'];
      $asset = $this->assetHelper->assetFromAssetId($selected_result);

      // Ensure the data attributes haven't been tampered with.
      if (!$asset) {
        $form_state->setErrorByName('search_results', $this->t('Invalid choice, please try again.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSelection(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $values = $form_state->getValues();
    if ($form_state->getErrors()) {
      return self::ajaxRenderFormAndMessages($form);
    }

    // Hidden input value set by javascript
    $selected_result = $form_state->getUserInput()['result_chosen'];
    $asset = EmbridgeAssetEntity::load($selected_result);
    $entity_id = $asset->get('id')->value;

    $values['entity_id'] = $entity_id;
    $response->addCommand(new EmbridgeSearchSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  public static function getSearchResults(EnterMediaDbClient $client, EnterMediaAssetHelper $asset_helper, array $filters = []) {
    $num_per_page = 20;
    $search_response = $client->search(1, $num_per_page, $filters);

    $render_array = [];
    foreach($search_response['results'] as $result) {

      $asset = $asset_helper->searchResultToAsset($result);

      $link_url = Url::fromUri($asset_helper->getAssetConversionUrl($asset, 'thumb'));
      $link_url->setOptions(array(
        'attributes' => array(
          'class' => array('embridge-choose-file'),
          'data-asset-id' => $asset->getAssetId(),
        ))
      );
      $link = Link::fromTextAndUrl('Choose Me', $link_url)->toRenderable();
      $render_array[] = [
        [
          '#theme' => 'embridge_image',
          '#asset' => $asset,
          '#conversion' => 'thumb',
          '#link_to' => '',
        ],
        $link
      ];
    }

    return $render_array;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function searchAjaxCallback(array &$form, FormStateInterface $form_state, Request $request) {
    return self::ajaxRenderFormAndMessages($form);
  }

  /**
   * @param array $form
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  protected static function ajaxRenderFormAndMessages(array &$form) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // Retrieve the element to be rendered.
    $status_messages = ['#type' => 'status_messages', '#weight' => -10];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

}
