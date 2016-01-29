<?php
/**
 * @file
 * Contains \Drupal|embridge\EnterMediaDbClient.
 */

namespace Drupal\embridge;


use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class EnterMediaDbClient implements EnterMediaDbClientInterface {

  const EMBRIDGE_LOGIN_PATH_DEFAULT = 'media/services/rest/login.xml';

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
  public function login() {
    $config = $this->configFactory->get('embridge.settings');
    $query_params = [
      'catalogid' => 'media',
      'accountname' => $config->get('username'),
      'password' => $config->get('password'),
    ];
    $query = UrlHelper::buildQuery($query_params);
    $login_path = self::EMBRIDGE_LOGIN_PATH_DEFAULT . '?' . $query;

    $request = $this->initRequest($login_path);
    try {
      $response = $this->httpClient->send($request);
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      if ($response === NULL) {
        throw new \Exception('Error connecting to EMDB backend');
      }
      if ($response->getStatusCode() == 403) {
        throw new \Exception('Failed to authenticate with EMDB, please check your settings.');
      }
    }
    if ($response->getStatusCode() != '200') {
      throw new \Exception('An unexpected response was returned from the Entity Pilot backend');
    }
    $body = (string) $response->getBody();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function initRequest($path = '') {
    $settings = $this->configFactory->get('embridge.settings');
    $uri = $settings->get('uri');
    $port = $settings->get('port');
    $request = new Request('POST', sprintf('%s:%s/%s', $uri, $port, $path));

    return $request;
  }
}