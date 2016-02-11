<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelper.
 */

namespace Drupal\embridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
  public function getAssetConversionUrl(EmbridgeAssetEntityInterface $asset, $conversion) {
    $settings = $this->configFactory->get('embridge.settings');
    $uri = $settings->get('uri');
    $application_id = $settings->get('application_id');

    $url = $uri . '/' . $application_id . '/views/modules/asset/downloads/preview/' . $conversion . '/' . $asset->getSourcePath() . '/thumb.jpg';

    return $url;
  }

  /**
   * Converts a search result to a Embridge Asset Entity.
   *
   * @param array $result
   *   A result from the search results.
   *
   * @return EmbridgeAssetEntityInterface
   *   The populated and saved entity.
   */
  public function searchResultToAsset($result) {
    if ($asset = $this->assetFromAssetId($result['id'])) {
      return $asset;
    }

    $storage = $this->entityTypeManager->getStorage('embridge_asset_entity');
    $values = [
      'asset_id' => $result['id'],
      'source_path' => $result['sourcepath'],
      'filename' => $result['name'],
      'filesize' => $result['filesize'],
      'filemime' => $this->mimeGuesser->guess($result['name']),
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
   *
   * @return EmbridgeAssetEntity|NULL
   *   Null if the asset didn't exist.
   */
  public function assetFromAssetId($asset_id) {
    /** @var SqlContentEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage('embridge_asset_entity');
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
