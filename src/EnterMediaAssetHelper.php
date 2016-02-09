<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelper.
 */

namespace Drupal\embridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\MimeType\MimeTypeGuesser;

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
   * @param \Drupal\Core\File\MimeType\MimeTypeGuesser
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
    $storage = $this->entityTypeManager->getStorage('embridge_asset_entity');
    $query = $storage->getQuery();
    $query->condition('asset_id', $result['id']);
    $query_result = $query->execute();

    if ($query_result) {
      return $storage->load($query_result['id']);
    }

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

}
