<?php
/**
 * @file
 * Contains \Drupal\embridge\EnterMediaAssetHelper.
 */

namespace Drupal\embridge;

use Drupal\Core\Config\ConfigFactoryInterface;

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
   * Constructs a new \Drupal\entity_pilot\Transport object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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

}
