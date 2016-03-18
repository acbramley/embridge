<?php

/**
 * @file
 * Contains \Drupal\embridge\Form\EmbridgeSearchForm.
 */

namespace Drupal\embridge\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\embridge\Ajax\EmbridgeSearchSave;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\embridge\EnterMediaDbClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EmbridgeSearchForm.
 *
 * @package Drupal\embridge\Form
 */
class EmbridgeSearchForm extends FormBase {

  const AJAX_WRAPPER_ID = 'embridge-results-wrapper';
  const MESSAGE_WRAPPER_ID = 'embridge-message-wrapper';

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
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * EmbridgeSearchForm constructor.
   *
   * @param \Drupal\embridge\EnterMediaDbClientInterface $embridge_client
   *   The embridge client.
   * @param \Drupal\embridge\EnterMediaAssetHelper $asset_helper
   *   A helper for asset entities.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(
    EnterMediaDbClientInterface $embridge_client,
    EnterMediaAssetHelper $asset_helper,
    EntityTypeManager $entity_type_manager,
    Renderer $renderer) {
    $this->client = $embridge_client;
    $this->assetHelper = $asset_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('embridge.client'),
      $container->get('embridge.asset_helper'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
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
  public function buildForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_config = NULL, $delta = 0) {
    $input = $form_state->getUserInput();

    // Store field information in $form_state.
    if (!static::getModalState($form_state)) {
      $field_state = array(
        'page' => 1,
      );
      static::setModalState($form_state, $field_state);
    }

    // Store for ajax commands.
    $form['delta'] = [
      '#type' => 'value',
      '#value' => $delta,
    ];

    // Get the field settings for filtering and validating files.
    if ($field_config) {
      $form['field_name'] = [
        '#type' => 'value',
        '#value' => $field_config->getName(),
      ];
      $field_settings = $field_config->getSettings();
      $extensions = $field_settings['file_extensions'];
      $catalog_id = $field_settings['catalog_id'];
    }
    else {
      // If we are coming from the ckeditor image dialog, we don't have field
      // config, look for extensions in the query string instead.
      // TODO: Think of a better way to do this.
      $query = \Drupal::request()->query->all();
      $extensions = $query['extensions'];
      $catalog_id = $query['catalog_id'];
    }

    $operation_options = [
      'startswith' => $this->t('Starts with'),
      'matches' => $this->t('Matches'),
    ];

    $form['filters'] = [
      // Opens div containing search form.
      '#type' => 'container',
      '#attributes' => [
        'class' => ['search-form'],
      ],
      'title' => ['#markup' => '<span class="search-title">' . $this->t('Search by filename') . '</span>'],
    ];
    $form['filters']['filename_op'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => $operation_options,
      '#default_value' => !empty($input['filename_op']) ? $input['filename_op'] : '',
    );

    $form['filters']['filename'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search by filename'),
      '#size' => 20,
      '#default_value' => !empty($input['filename']) ? $input['filename'] : '',
    );

    $ajax_settings = [
      'callback' => [$this, 'searchAjax'],
      'wrapper' => self::AJAX_WRAPPER_ID,
      'effect' => 'fade',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
    $form['filters']['search'] = [
      '#type' => 'submit',
      '#submit' => [[$this, 'searchSubmit']],
      '#ajax' => $ajax_settings,
      '#value' => $this->t('Search'),
      // Hide the button.
      '#attributes' => array(
        'class' => array(
          'embridge-ajax-search-submit',
        ),
      ),
    ];

    $filters = [];
    $filters[] = [
      'field' => 'fileformat',
      'operator' => 'matches',
      'value' => $this->massageExtensionSetting($extensions),
    ];
    // Static list of known search filters from EMDB.
    // TODO: Make this configurable.
    $known_search_filters = [
      'libraries',
      'assettype',
      'fileformat',
    ];
    // Add filename filter as this always exists.
    if (!empty($input['filename_op'])) {
      $filters[] = [
        'field' => 'name',
        'operator' => $input['filename_op'],
        'value' => $input['filename'],
      ];
    }

    // Add user chosen filters.
    foreach ($known_search_filters as $filter_id) {
      if (empty($input[$filter_id])) {
        continue;
      }

      $filters[] = [
        'field' => $filter_id,
        'operator' => 'matches',
        'value' => $input[$filter_id],
      ];
    }

    // Execute a search.
    $modal_state = static::getModalState($form_state);
    $page = $modal_state['page'];
    $search_response = $this->getSearchResults($page, $filters);

    $title = $this->t('More search options');
    $form['filters']['extra_filters'] = [
      '#markup' => '<div class="moreOptions"><a href="#options" data-toggle="collapse">' . $title . '</a><div id="options" class="collapse">',
    ];
    // Add filters from search response.
    if (!empty($search_response['filteroptions'])) {
      foreach ($search_response['filteroptions'] as $filter) {
        if (!in_array($filter['id'], $known_search_filters)) {
          continue;
        }
        // "Empty" option.
        $filter_options = [$this->t('-- Select --')];

        // Add each option to the list.
        foreach ($filter['children'] as $option) {
          $filter_options[$option['id']] = $this->t('@name (@count)', ['@name' => $option['name'], '@count' => $option['count']]);
        }
        $form['filters']['extra_filters'][$filter['id']] = [
          '#type' => 'select',
          '#title' => $this->t('@name', ['@name' => $filter['name']]),
          '#options' => $filter_options,
          '#default_value' => !empty($input[$filter['id']]) ? $input[$filter['id']] : '',
        ];
      }
    }
    $form['filters']['close_extra_filters'] = [
      // Start the 'More search options' wrapper.
      '#markup' => '</div></div>',
    ];

    if (!empty($search_response['results'])) {
      $form['search_results'] = [
        '#theme' => 'embridge_search_results',
        '#results' => $this->formatSearchResults($search_response, $this->assetHelper, $catalog_id),
      ];
    }
    else {
      $form['search_results'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>No files found, please adjust your filters and try again.</p>'),
      ];
    }
    // Add "previous page" pager.
    $form['page_previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous page'),
      '#submit' => [[$this, 'previousPageSubmit']],
      '#ajax' => array(
        'callback' => [$this, 'searchAjax'],
        'wrapper' => self::AJAX_WRAPPER_ID,
        'effect' => 'fade',
      ),
      // Always display it for consistency, only enable it if we can go back.
      '#disabled' => !($page > 1),
      '#prefix' => '<div class="pagination">',
    ];

    // Add "next page" pager.
    $form['page_next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next page'),
      '#submit' => [[$this, 'nextPageSubmit']],
      '#ajax' => array(
        'callback' => [$this, 'searchAjax'],
        'wrapper' => self::AJAX_WRAPPER_ID,
        'effect' => 'fade',
      ),
      // Always display it for consistency, only enable it if we can go forward.
      '#disabled' => !($search_response['response']['pages'] > $search_response['response']['page']),
      '#suffix' => '</div>',
    ];

    $form['result_chosen'] = [
      '#type' => 'hidden',
      '#value' => !empty($input['result_chosen']) ? $input['result_chosen'] : '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => [$this, 'selectItemAjax'],
        'event' => 'click',
      ),
      // Hide the button.
      '#attributes' => array(
        'class' => array(
          'embridge-ajax-select-file',
          'hidden-button',
        ),
      ),
    ];

    $form['#attached']['library'][] = 'embridge/embridge.lib';
    $form['#prefix'] = '<div id="' . self::AJAX_WRAPPER_ID . '"><div id="' . self::MESSAGE_WRAPPER_ID . '"></div>';
    $form['#sufix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button == 'submit') {
      $entity_id = $form_state->getUserInput()['result_chosen'];
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('embridge_asset_entity');
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $asset */
      $asset = $storage->load($entity_id);

      // Ensure the data attributes haven't been tampered with.
      if (!$asset) {
        $form_state->setError($form['search_results'], $this->t('Invalid choice, please try again.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * Submission handler for the "Previous page" button.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function previousPageSubmit(array $form, FormStateInterface $form_state) {
    // Increment the page count.
    $modal_state = self::getModalState($form_state);
    $modal_state['page']--;
    self::setModalState($form_state, $modal_state);

    $form_state->setRebuild();
  }

  /**
   * Submission handler for the "Next page" button.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function nextPageSubmit(array $form, FormStateInterface $form_state) {
    // Increment the page count.
    $modal_state = self::getModalState($form_state);
    $modal_state['page']++;
    self::setModalState($form_state, $modal_state);

    $form_state->setRebuild();
  }

  /**
   * Queries EnterMedia for assets matching search filter.
   *
   * @param int $page
   *   The page to return.
   * @param array $filters
   *   An array of filters.
   *
   * @return array
   *   A search response array.
   */
  private function getSearchResults($page, array $filters = []) {
    $num_per_page = 8;
    $search_response = $this->client->search($page, $num_per_page, $filters);

    return $search_response;
  }

  /**
   * Formats a response from EMDB to be themed into results.
   *
   * @param array $search_response
   *   The response.
   * @param \Drupal\embridge\EnterMediaAssetHelper $asset_helper
   *   A helper service.
   * @param string $catalog_id
   *   An ID of the catalog to save against temp assets.
   *
   * @return array
   *   A renderable array.
   */
  private function formatSearchResults(array $search_response, EnterMediaAssetHelper $asset_helper, $catalog_id) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('embridge_catalog');
    /** @var \Drupal\embridge\EmbridgeCatalogInterface $catalog */
    $catalog = $storage->load($catalog_id);
    $application_id = $catalog->getApplicationId();

    $render_array = [];
    foreach ($search_response['results'] as $result) {

      $asset = $asset_helper->searchResultToAsset($result, $catalog_id);
      $render_array[$asset->id()] = [
        '#theme' => 'embridge_image',
        '#asset' => $asset,
        '#conversion' => 'thumb',
        '#link_to' => '',
        '#application_id' => $application_id,
      ];
    }

    return $render_array;
  }

  /**
   * Submission handler for the "Search" button.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function searchSubmit(array $form, FormStateInterface $form_state) {
    // Reset the pager.
    $modal_state = self::getModalState($form_state);
    $modal_state['page'] = 1;
    self::setModalState($form_state, $modal_state);

    $form_state->setRebuild();
  }

  /**
   * When searching, simply return a ajax response.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response to replace the form.
   */
  public function searchAjax(array &$form, FormStateInterface $form_state) {
    return $this->ajaxRenderFormAndMessages($form);
  }

  /**
   * Renders form and status messages and returns an ajax response.
   *
   * Used for both submission buttons.
   *
   * @param array $form
   *   The form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response to replace the form.
   */
  protected function ajaxRenderFormAndMessages(array &$form) {
    $response = new AjaxResponse();

    // Retrieve the element to be rendered.
    $status_messages = ['#type' => 'status_messages', '#weight' => -10];
    // For some crazy reason, if we do this inline in the replace command, it
    // breaks ajax functionality entirely.
    $output = $this->renderer->renderRoot($form);
    $messages = $this->renderer->renderRoot($status_messages);

    $message_wrapper_id = '#' . self::MESSAGE_WRAPPER_ID;

    $response->setAttachments($form['#attached']);
    $response->addCommand(new ReplaceCommand(NULL, $output));
    $response->addCommand(new HtmlCommand($message_wrapper_id, ''));
    $response->addCommand(new AppendCommand($message_wrapper_id, $messages));

    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function selectItemAjax(array &$form, FormStateInterface $form_state) {
    $errors = $form_state->getErrors();
    if ($errors) {
      return self::ajaxRenderFormAndMessages($form);
    }
    $response = new AjaxResponse();

    // Hidden input value set by javascript.
    $values = $form_state->getValues();
    $values['entity_id'] = $form_state->getUserInput()['result_chosen'];
    $response->addCommand(new EmbridgeSearchSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function getModalState(FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), ['search_results']);
  }

  /**
   * {@inheritdoc}
   */
  public static function setModalState(FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), ['search_results'], $field_state);
  }

  /**
   * Massages the field setting for the allowed extensions into a search filter.
   *
   * @param string $extensions
   *   The field setting for allowed file extensions.
   *
   * @return string
   *   The string for a search filter.
   */
  private function massageExtensionSetting($extensions) {
    // Setting allows either space or comma separation, so replace both.
    $extensions = str_replace(' ', '|', $extensions);
    $extensions = str_replace(',', '|', $extensions);

    return $extensions;
  }

}
