<?php

/**
 * @file
 * Contains \Drupal\embridge_ckeditor\Plugin\CKEditorPlugin\EmbridgeImage.
 */

namespace Drupal\embridge_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\embridge\Entity\EmbridgeCatalog;

/**
 * Defines the "embridgeimage" plugin.
 *
 * @CKEditorPlugin(
 *   id = "embridgeimage",
 *   label = @Translation("EMBridge Image"),
 *   module = "embridge_ckeditor"
 * )
 */
class EmbridgeImage extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {
  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'embridge_ckeditor') . '/js/plugins/embridgeimage/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'embridgeImage_dialogTitleAdd' => t('Insert Image'),
      'embridgeImage_dialogTitleEdit' => t('Edit Image'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'EmbridgeImage' => [
        'label' => t('EMBridge Image'),
        'image' => drupal_get_path('module', 'embridge_ckeditor') . '/js/plugins/embridgeimage/image.png',
      ],
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $editor_settings = $editor->getSettings();
    $plugin_settings = !empty($editor_settings['plugins']['embridgeimage']['embridge_image_upload']) ? $editor_settings['plugins']['embridgeimage']['embridge_image_upload'] : [];
    $plugin_settings += [
      'directory' => 'embridge-inline-images',
      'max_size' => '',
      'catalog' => '',
    ];
    $sub_form = [];

    $sub_form['warning'] = [
      '#markup' => $this->t("<strong>Warning: This plugin is not compatible with core's image dialog.</strong>"),
    ];
    $sub_form['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $plugin_settings['directory'],
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t("A temporary directory to upload images into before sending to EMDB."),
    ];
    $default_max_size = format_size(file_upload_max_size());
    $sub_form['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $plugin_settings['max_size'],
      '#title' => $this->t('Maximum file size'),
      '#description' => $this->t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]
      ),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
    ];
    /** @var EmbridgeCatalog[] $entities */
    $entities = EmbridgeCatalog::loadMultiple();

    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    $sub_form['catalog_id'] = [
      '#type' => 'select',
      '#title' => t('Catalog'),
      '#default_value' => $plugin_settings['catalog'],
      '#options' => $options,
      '#description' => $this->t("Select the Catalog to source media from for this field."),
      '#required' => TRUE,
      '#weight' => 6,
    ];

    $sub_form['#attached']['library'][] = 'embridge_ckeditor/drupal.embridge_ckeditor.embridgeimage.admin';

    $form['embridge_image_upload'] = $sub_form;

    return $form;
  }

}
