<?php

/**
 * @file
 * Contains \Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog.
 */

namespace Drupal\embridge_ckeditor\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\embridge\EnterMediaAssetHelperInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an embridge image dialog for text editors.
 */
class EmbridgeCkeditorImageDialog extends FormBase {

  const AJAX_WRAPPER_ID = 'embridge-ckeditor-image-dialog-form';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The asset helper service.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelperInterface
   */
  protected $assetHelper;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\embridge\EnterMediaAssetHelperInterface $asset_helper
   *   The asset helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EnterMediaAssetHelperInterface $asset_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->assetHelper = $asset_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('embridge.asset_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'embridge_ckeditor_image_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    $user_input = $form_state->getUserInput();
    if (isset($user_input['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the image dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $image_element = $user_input['editor_object'];
      $form_state->set('image_element', $image_element);
      $form_state->setCached(TRUE);
    }
    // Coming from the wizard? Look in temporary storage.
    elseif ($form_state->getTemporaryValue('wizard')) {
      $image_element = $form_state->getTemporaryValue('wizard')['image_element'];
    }
    else {
      // Retrieve the image element's attributes from form state.
      $image_element = $form_state->get('image_element') ?: [];
    }

    // Add libraries and wrap the form in ajax wrappers.
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="' . self::AJAX_WRAPPER_ID . '">';
    $form['#suffix'] = '</div>';

    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = $this->entityTypeManager->getStorage('editor')->load($filter_format->id());

    // Construct strings to use in the upload validators.
    $embridge_image_settings = $editor->getSettings()['plugins']['embridgeimage']['embridge_image_upload'];
    $max_filesize = min(Bytes::toInt($embridge_image_settings['max_size']), file_upload_max_size());

    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $existing_asset */
    $existing_asset = isset($image_element['data-entity-uuid']) ? $this->entityRepository->loadEntityByUuid('embridge_asset_entity', $image_element['data-entity-uuid']) : NULL;
    $asset_id = $existing_asset ? $existing_asset->id() : NULL;

    /** @var \Drupal\embridge\EmbridgeCatalogInterface $catalog */
    $catalog = $this->entityTypeManager->getStorage('embridge_catalog')->load($embridge_image_settings['catalog_id']);

    // Create a preview image.
    $preview = FALSE;
    if (!empty($user_input['_triggering_element_name'])) {
      $triggering_element = $user_input['_triggering_element_name'];
    }

    // If we are editing an existing asset, use that thumbnail.
    if (empty($form_state->getValues()) && $existing_asset) {
      $preview = $this->assetHelper->getAssetConversionUrl(
        $existing_asset,
        $catalog->getApplicationId(),
        'thumb'
      );
    }
    // Form state values are still populated when an existing image is edited,
    // then the remove button is clicked. So ensure we haven't clicked the
    // button before loading that as well.
    elseif (isset($triggering_element) && $triggering_element != 'asset_remove_button' && $uploaded_id = $form_state->getValue(['asset', 0])) {
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $uploaded_asset */
      $uploaded_asset = $this->entityTypeManager->getStorage('embridge_asset_entity')->load($uploaded_id);

      if ($uploaded_asset) {
        $preview = $this->assetHelper->getAssetConversionUrl(
          $uploaded_asset,
          $catalog->getApplicationId(),
          'thumb'
        );
      }
    }

    // Use a stock image for preview.
    if (!$preview) {
      $preview = drupal_get_path('module', 'embridge_ckeditor') . '/images/preview-image.png';
    }
    // TODO: Make this configurable.
    $allowed_extensions = 'gif png jpg jpeg';

    $url_options = [
      'filter_format' => $filter_format->id(),
      'extensions' => $allowed_extensions,
      'catalog_id' => $embridge_image_settings['catalog_id'],
    ];
    $link_url = Url::fromRoute('embridge_ckeditor.image.wizard', $url_options);
    $link_url->setOptions(
      [
        'attributes' => [
          'class' => ['use-ajax', 'button'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 1000]),
        ],
      ]
    );

    $form['asset'] = [
      'preview' => [
        '#theme' => 'image',
        '#uri' => $preview,
        '#weight' => -100,
      ],
      '#title' => $this->t('Image'),
      '#type' => 'embridge_asset',
      '#catalog_id' => $embridge_image_settings['catalog_id'],
      '#upload_location' => 'public://' . $embridge_image_settings['directory'],
      '#default_value' => $asset_id ? [$asset_id] : NULL,
      '#upload_validators' => [
        'validateFileExtensions' => [$allowed_extensions],
        'validateFileSize' => [$max_filesize],
      ],
      '#delta' => 0,
      '#allow_search' => FALSE,
      '#required' => TRUE,
      'search_link' => Link::fromTextAndUrl('Search existing', $link_url)->toRenderable(),
    ];

    $form['attributes']['src'] = [
      '#type' => 'value',
    ];

    $alt = isset($image_element['alt']) ? $image_element['alt'] : '';
    $form['attributes']['alt'] = [
      '#title' => $this->t('Alternative text'),
      '#description' => $this->t('If the image imparts meaning, describe it in teh alt text. If the image is purely decorative, the alt text can remain blank.'),
      '#type' => 'textfield',
      '#default_value' => $alt,
      '#maxlength' => 2048,
    ];

    $conversion = isset($image_element['data-conversion']) ? $image_element['data-conversion'] : '';
    $conversions_array = $catalog->getConversionsArray();
    $form['attributes']['data-conversion'] = [
      '#title' => $this->t('Conversion'),
      '#description' => $this->t('Choose the conversion to display'),
      '#type' => 'select',
      '#default_value' => $conversion,
      '#options' => array_combine($conversions_array, $conversions_array),
    ];

    // When Drupal core's filter_align is being used, the text editor may
    // offer the ability to change the alignment.
    if ($filter_format->filters('filter_align')->status) {
      $data_align = !empty($image_element['data-align']) ? $image_element['data-align'] : '';
      $form['attributes']['data-align'] = [
        '#title' => $this->t('Align'),
        '#type' => 'select',
        '#options' => [
          'none' => $this->t('None'),
          'left' => $this->t('Left'),
          'center' => $this->t('Center'),
          'right' => $this->t('Right'),
        ],
        '#default_value' => $data_align,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => [$this, 'ajaxSave'],
        'event' => 'click',
      ],
    ];

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
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $asset_id = $form_state->getValue(array('asset', 0));
    if (!empty($asset_id)) {
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $asset */
      $asset = $this->entityTypeManager->getStorage('embridge_asset_entity')->load($asset_id);

      // We need to make sure the asset is permanent at this point.
      if ($asset->isTemporary()) {
        $asset->setPermanent();
        $asset->save();
      }

      /** @var \Drupal\embridge\EmbridgeCatalogInterface $catalog */
      $catalog = $this->entityTypeManager->getStorage('embridge_catalog')->load($form['asset']['#catalog_id']);
      $conversion = $form_state->getValue(['attributes', 'data-conversion']);
      $source_url = $this->assetHelper->getAssetConversionUrl($asset, $catalog->getApplicationId(), $conversion);

      $form_state->setValue(['attributes', 'src'], $source_url);
      $form_state->setValue(['attributes', 'data-entity-uuid'], $asset->uuid());
      $form_state->setValue(['attributes', 'data-entity-type'], 'embridge_asset_entity');
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#' . self::AJAX_WRAPPER_ID, $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
