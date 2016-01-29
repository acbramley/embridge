<?php
/**
 * @file
 * Contains \Drupal|embridge\EnterMediaDbClient.
 */

namespace Drupal\embridge;


use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

class EnterMediaDbClient implements EnterMediaDbClientInterface {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * JSON Encoder.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $jsonEncoder;

  /**
   * Constructs a new \Drupal\entity_pilot\Transport object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client service.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The json serializer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $client, SerializationInterface $serializer) {
    $this->configFactory = $config_factory;
    $this->jsonEncoder = $serializer;
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function initRequest() {
    $request = new Request();

    return $request;
  }
}