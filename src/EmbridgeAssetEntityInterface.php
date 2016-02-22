<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeAssetEntityInterface.
 */

namespace Drupal\embridge;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Embridge asset entity entities.
 *
 * @ingroup embridge
 */
interface EmbridgeAssetEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Embridge asset id.
   *
   * @return string
   *   Id of the Embridge asset entity from EnterMedia.
   */
  public function getAssetId();

  /**
   * Sets the Embridge asset id.
   *
   * @param string $asset_id
   *   The asset id.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setAssetId($asset_id);

  /**
   * Gets the Embridge asset catalog id.
   *
   * @return string
   *   Catalog id of the Embridge asset entity.
   */
  public function getCatalogId();

  /**
   * Sets the Embridge asset catalog id.
   *
   * @param string $catalog_id
   *   The catalog id.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setCatalogId($catalog_id);

  /**
   * Gets the Embridge asset filename name.
   *
   * @return string
   *   Name of the Embridge asset entity.
   */
  public function getFilename();

  /**
   * Sets the Embridge asset filename name.
   *
   * @param string $name
   *   The Embridge asset entity name.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setFilename($name);

  /**
   * Gets the Embridge asset source path.
   *
   * @return string
   *   Source path of the Embridge asset entity.
   */
  public function getSourcePath();

  /**
   * Sets the Embridge asset source path.
   *
   * @param string $uri
   *   The asset's source path.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setSourcePath($uri);

  /**
   * Gets the Embridge asset thumbnail uri.
   *
   * @return string
   *   Thumbnail uri of the Embridge asset entity.
   */
  public function getThumbnail();

  /**
   * Sets the Embridge asset thumbnail uri.
   *
   * @param string $uri
   *   The thumbnail uri.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setThumbnail($uri);

  /**
   * Gets the Embridge asset preview uri.
   *
   * @return string
   *   Preview uri of the Embridge asset entity.
   */
  public function getPreview();

  /**
   * Sets the Embridge asset preview uri.
   *
   * @param string $uri
   *   The preview uri.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setPreview($uri);

  /**
   * Gets the Embridge asset width.
   *
   * @return string
   *   Width of the Embridge asset entity.
   */
  public function getWidth();

  /**
   * Sets the Embridge asset width.
   *
   * @param string $width
   *   The asset width.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setWidth($width);

  /**
   * Gets the Embridge asset height.
   *
   * @return string
   *   Height of the Embridge asset entity.
   */
  public function getHeight();

  /**
   * Sets the Embridge asset height.
   *
   * @param string $height
   *   The asset height.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setHeight($height);

  /**
   * Gets the Embridge asset file mime.
   *
   * @return string
   *   File mime of the Embridge asset entity.
   */
  public function getMimeType();

  /**
   * Sets the Embridge asset file mime type.
   *
   * @param string $mime
   *   The file mime.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setMimeType($mime);

  /**
   * Gets the Embridge asset file size.
   *
   * @return int
   *   File size of the Embridge asset entity.
   */
  public function getSize();

  /**
   * Sets the Embridge asset file size.
   *
   * @param string $size
   *   The file size.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setSize($size);

  /**
   * Gets the Embridge asset embed code.
   *
   * @return string
   *   Embed code of the Embridge asset entity.
   */
  public function getEmbedCode();

  /**
   * Sets the Embridge asset embed code.
   *
   * @param string $embed_code
   *   The embed code.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setEmbedCode($embed_code);

  /**
   * Gets the Embridge asset entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Embridge asset entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Embridge asset entity creation timestamp.
   *
   * @param int $timestamp
   *   The Embridge asset entity creation timestamp.
   *
   * @return \Drupal\embridge\EmbridgeAssetEntityInterface
   *   The called Embridge asset entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns TRUE if the file is permanent.
   *
   * @return bool
   *   TRUE if the file status is permanent.
   */
  public function isPermanent();

  /**
   * Returns TRUE if the file is temporary.
   *
   * @return bool
   *   TRUE if the file status is temporary.
   */
  public function isTemporary();

  /**
   * Sets the file status to permanent.
   */
  public function setPermanent();

  /**
   * Sets the file status to temporary.
   */
  public function setTemporary();

}
