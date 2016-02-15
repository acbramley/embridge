<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelper.
 */

namespace Drupal\embridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\embridge\Entity\EmbridgeAssetEntity;

/**
 * Class EnterMediaAssetHelper.
 *
 * @package Drupal\embridge
 */
class EnterMediaAssetHelper implements EnterMediaAssetHelperInterface {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * Mime type guesser service.
   *
   * @var \Drupal\Core\File\MimeType\MimeTypeGuesser
   */
  protected $mimeGuesser;

  /**
   * Constructs a new \Drupal\entity_pilot\Transport object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager class.
   * @param \Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser
   *   The mime type guesser service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, MimeTypeGuesser $mime_guesser) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->mimeGuesser = $mime_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssetConversionUrl(EmbridgeAssetEntityInterface $asset, $application_id, $conversion) {
    $settings = $this->configFactory->get('embridge.settings');
    $uri = $settings->get('uri');

    $url = $uri . '/' . $application_id . '/views/modules/asset/downloads/preview/' . $conversion . '/' . $asset->getSourcePath() . '/thumb.jpg';

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function searchResultToAsset($result, $catalog_id) {
    /** @var EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('embridge_asset_entity');

    if ($asset = $this->loadFromAssetId($result['id'], $storage)) {
      return $asset;
    }

    $values = [
      'asset_id' => $result['id'],
      'source_path' => $result['sourcepath'],
      'filename' => $result['name'],
      'filesize' => $result['filesize'],
      'filemime' => $this->mimeGuesser->guess($result['name']),
      'catalog_id' => $catalog_id,
    ];

    /** @var EmbridgeAssetEntityInterface $asset */
    $asset = $storage->create($values);
    $asset->setTemporary();
    $asset->save();

    return $asset;
  }

  /**
   * Returns an asset entity given an asset ID.
   *
   * @param string $asset_id
   *   The asset ID property to check for.
   * @param EntityStorageInterface $storage
   *   The storage to load from.
   *
   * @return EmbridgeAssetEntity|NULL
   *   Null if the asset didn't exist.
   */
  private function loadFromAssetId($asset_id, EntityStorageInterface $storage) {
    $query = $storage->getQuery();
    $query->condition('asset_id', $asset_id);
    $query_result = $query->execute();

    if ($query_result) {
      $id = array_pop($query_result);
      return $storage->load($id);
    }
    return NULL;
  }
}
