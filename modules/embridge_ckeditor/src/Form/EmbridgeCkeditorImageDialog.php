<?php

/**
 * @file
 * Contains \Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog.
 */

namespace Drupal\embridge_ckeditor\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\embridge\EnterMediaAssetHelperInterface;
use Drupal\embridge\Entity\EmbridgeCatalog;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an image dialog for text editors.
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
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the image dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $image_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('image_element', $image_element);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the image element's attributes from form state.
      $image_element = $form_state->get('image_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="' . self::AJAX_WRAPPER_ID . '">';
    $form['#suffix'] = '</div>';

    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = $this->entityTypeManager->getStorage('editor')->load($filter_format->id());

    // Construct strings to use in the upload validators.
    $embridge_image_settings = $editor->getSettings()['plugins']['embridgeimage']['embridge_image_upload'];
    $max_filesize = min(Bytes::toInt($embridge_image_settings['max_size']), file_upload_max_size());

    $existing_asset = isset($image_element['data-entity-uuid']) ? $this->entityRepository->loadEntityByUuid('embridge_asset_entity', $image_element['data-entity-uuid']) : NULL;
    $asset_id = $existing_asset ? $existing_asset->id() : NULL;

    $form['fid'] = array(
      '#title' => $this->t('Image'),
      '#type' => 'embridge_asset',
      '#catalog_id' => $embridge_image_settings['catalog_id'],
      '#upload_location' => 'public://' . $embridge_image_settings['directory'],
      '#default_value' => $asset_id ? array($asset_id) : NULL,
      '#upload_validators' => array(
        'validateFileExtensions' => array('gif png jpg jpeg'),
        'validateFileSize' => array($max_filesize),
      ),
      '#allow_search' => FALSE,
      '#required' => TRUE,
    );

    $form['attributes']['src'] = array(
      '#type' => 'value',
    );

    $alt = isset($image_element['alt']) ? $image_element['alt'] : '';
    $form['attributes']['alt'] = array(
      '#title' => $this->t('Alternative text'),
      '#description' => $this->t('If the image imparts meaning, describe it in teh alt text. If the image is purely decorative, the alt text can remain blank.'),
      '#type' => 'textfield',
      '#default_value' => $alt,
      '#maxlength' => 2048,
    );

    // When Drupal core's filter_align is being used, the text editor may
    // offer the ability to change the alignment.
    if ($filter_format->filters('filter_align')->status) {
      $data_align = !empty($image_element['data-align']) ? $image_element['data-align'] : '';
      $form['attributes']['data-align'] = array(
        '#title' => $this->t('Align'),
        '#type' => 'select',
        '#options' => array(
          'none' => $this->t('None'),
          'left' => $this->t('Left'),
          'center' => $this->t('Center'),
          'right' => $this->t('Right'),
        ),
        '#default_value' => $data_align,
      );
    }

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(array('fid', 0));
    if (!empty($fid)) {
      /** @var \Drupal\embridge\EmbridgeAssetEntityInterface $asset */
      $asset = $this->entityTypeManager->getStorage('embridge_asset_entity')->load($fid);
      /** @var \Drupal\embridge\EmbridgeCatalogInterface $catalog */
      $catalog = $this->entityTypeManager->getStorage('embridge_catalog')->load($form['fid']['#catalog_id']);
      $source_url = $this->assetHelper->getAssetConversionUrl($asset, $catalog->getApplicationId(), 'thumb');

      $form_state->setValue(array('attributes', 'src'), $source_url);
      $form_state->setValue(array('attributes', 'data-entity-uuid'), $asset->uuid());
      $form_state->setValue(array('attributes', 'data-entity-type'), 'embridge_asset_entity');
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
