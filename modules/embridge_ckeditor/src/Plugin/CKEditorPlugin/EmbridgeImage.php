<?php

/**
 * @file
 * Contains \Drupal\embridge_ckeditor\Plugin\CKEditorPlugin\EmbridgeImage.
 */

namespace Drupal\embridge_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\Component\Plugin\PluginBase;
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
class EmbridgeImage extends PluginBase implements CKEditorPluginInterface, CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {
  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

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
    return ['embridge_ckeditor/embridge_ckeditor.dialog.lib'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
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
      'catalog_id' => '',
      'enabled' => FALSE,
    ];
    $sub_form = [];

    $sub_form['warning'] = [
      '#markup' => t("<strong>Warning: This plugin overrides the standard drupal image plugin.</strong>"),
    ];
    $sub_form['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $plugin_settings['enabled'],
      '#title' => t('Enable plugin'),
      '#description' => t("Tick this to connect the image dialog with EMDB."),
      '#attributes' => array(
        'data-editor-embridge-upload' => 'enabled',
      ),
    ];
    $show_if_plugin_uploads_enabled = array(
      'visible' => array(
        ':input[data-editor-embridge-upload="enabled"]' => array('checked' => TRUE),
      ),
    );
    $sub_form['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $plugin_settings['directory'],
      '#title' => t('Upload directory'),
      '#description' => t("A temporary directory to upload images into before sending to EMDB."),
      '#states' => $show_if_plugin_uploads_enabled,
    ];
    $default_max_size = format_size(file_upload_max_size());
    $sub_form['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $plugin_settings['max_size'],
      '#title' => t('Maximum file size'),
      '#description' => t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]
      ),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
      '#states' => $show_if_plugin_uploads_enabled,
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
      '#default_value' => $plugin_settings['catalog_id'],
      '#options' => $options,
      '#description' => t("Select the Catalog to source media from for this field."),
      '#required' => TRUE,
      '#weight' => 6,
      '#states' => $show_if_plugin_uploads_enabled,
    ];
    $sub_form['library_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Library'),
      '#default_value' => $plugin_settings['library_id'],
      '#description' => t("Limit uploads via this field to a specific library."),
      '#required' => FALSE,
      '#size' => 10,
      '#weight' => 6,
    );

    $sub_form['#attached']['library'][] = 'embridge_ckeditor/drupal.embridge_ckeditor.embridgeimage.admin';

    $form['embridge_image_upload'] = $sub_form;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $enabled = FALSE;

    if (!$editor->hasAssociatedFilterFormat()) {
      return $enabled;
    }

    $editor_settings = $editor->getSettings();
    $plugin_settings = !empty($editor_settings['plugins']['embridgeimage']['embridge_image_upload']) ? $editor_settings['plugins']['embridgeimage']['embridge_image_upload'] : [];

    if (!empty($plugin_settings['enabled']) && $plugin_settings['enabled']) {
      $enabled = TRUE;
    }

    return $enabled;
  }

}
