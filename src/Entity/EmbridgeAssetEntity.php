<?php

/**
 * @file
 * Contains \Drupal\embridge\Entity\EmbridgeAssetEntity.
 */

namespace Drupal\embridge\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Embridge asset entity entity.
 *
 * @ingroup embridge
 *
 * @ContentEntityType(
 *   id = "embridge_asset_entity",
 *   label = @Translation("Embridge asset entity"),
 *   handlers = {
 *     "views_data" = "Drupal\embridge\Entity\EmbridgeAssetEntityViewsData",
 *     "access" = "Drupal\embridge\EmbridgeAssetEntityAccessControlHandler",
 *   },
 *   base_table = "embridge_asset_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uid" = "uid",
 *     "label" = "filename",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 * )
 */
class EmbridgeAssetEntity extends ContentEntityBase implements EmbridgeAssetEntityInterface {
  use EntityChangedTrait;
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCatalogId() {
    return $this->get('catalog_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCatalogId($catalog_id) {
    $this->set('catalog_id', $catalog_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename() {
    return $this->get('filename')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilename($name) {
    $this->set('filename', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePath() {
    return $this->get('source_path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourcePath($uri) {
    $this->get('source_path')->value = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail() {
    return $this->get('thumbnail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setThumbnail($uri) {
    $this->get('thumbnail')->value = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreview() {
    return $this->get('preview')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreview($uri) {
    $this->get('preview')->value = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->get('width')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->get('width')->value = $width;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->get('height')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->get('height')->value = $height;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->get('filemime')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMimeType($mime) {
    $this->get('filemime')->value = $mime;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->get('filesize')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSize($size) {
    $this->get('filesize')->value = $size;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmbedCode() {
    return $this->get('embedcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmbedCode($embed_code) {
    $this->get('embedcode')->value = $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The internal ID of the Embridge asset.'))
      ->setReadOnly(TRUE);

    $fields['asset_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Asset ID'))
      ->setDescription(t('The Enter Media ID of the Embridge asset.'))
      ->setReadOnly(TRUE);

    $fields['catalog_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Catalog ID'))
      ->setDescription(t('EnterMedia catalog ID.'));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Embridge asset entity.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Id'))
      ->setDescription(t('The user ID of author of the Embridge asset entity.'))
      ->setSetting('target_type', 'user');

    $fields['filename'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename'))
      ->setDescription(t('The name of the Embridge asset file.'));

    $fields['source_path'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Source path'))
      ->setDescription(t('EnterMedia asset source path.'))
      ->setSetting('max_length', 255)
      ->setSetting('case_sensitive', TRUE)
      ->addConstraint('FileUriUnique');

    $fields['thumbnail'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Thumbnail path'))
      ->setDescription(t('EnterMedia asset thumbnail path.'))
      ->setSetting('max_length', 255)
      ->setSetting('case_sensitive', TRUE);

    $fields['preview'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Preview path'))
      ->setDescription(t('EnterMedia asset preview path.'))
      ->setSetting('max_length', 255)
      ->setSetting('case_sensitive', TRUE);

    $fields['width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('EnterMedia asset width.'))
      ->setSetting('unsigned', TRUE);

    $fields['height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('EnterMedia asset height.'))
      ->setSetting('unsigned', TRUE);

    $fields['filemime'] = BaseFieldDefinition::create('string')
      ->setLabel(t('File MIME type'))
      ->setSetting('is_ascii', TRUE)
      ->setDescription(t("Enter Media asset's MIME type."));

    $fields['filesize'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('File size'))
      ->setDescription(t('The size of the enter media asset in bytes.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

    $fields['embedcode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Embed code'))
      ->setDescription(t("EnterMedia asset embed code."));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Embridge asset entity entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
