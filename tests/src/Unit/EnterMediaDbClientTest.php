<?php

/**
 * @file
 * Contains Drupal\Tests\embridge\Unit\EnterMediaDbClientTest
 */

namespace Drupal\Tests\embridge\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\embridge\EnterMediaDbClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Class EnterMediaDbClientTest
 *
 * @package Drupal\Tests\embridge\Unit
 * @coversDefaultClass \Drupal\embridge\EnterMediaDbClient
 */
class EnterMediaDbClientTest extends UnitTestCase {

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $client;

  /**
   * Serializer.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Our client.
   *
   * @var \Drupal\embridge\EnterMediaDbClient
   */
  protected $emdbClient;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->client = $this->getMock(ClientInterface::class);
    $this->serializer = new Json();
    $sample_config = [
      'url' => 'www.example.com',
      'port' => '8080',
      'username' => 'admin',
      'password' => 'admin',
    ];
    $mockConfig = $this->getMockBuilder(ImmutableConfig::class)->disableOriginalConstructor()->getMock();
    $mockConfig
      ->expects($this->once())
      ->method('get')
      ->with('url')
      ->willReturn($sample_config['url']);
    $mockConfig
      ->expects($this->once())
      ->method('get')
      ->with('port')
      ->willReturn($sample_config['port']);

    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('embridge.settings')
      ->willReturn($mockConfig);

    $this->emdbClient = new EnterMediaDbClient($this->configFactory, $this->client, $this->serializer);
  }

  /**
   * Tests that initRequest() uses the config factory and populates an object.
   *
   * @covers ::initRequest
   * @test
   */
  public function initRequestReturnsRequestObjectPopulatedWithConfig() {
    $request = $this->emdbClient->initRequest();
    $this->assertInstanceOf('\GuzzleHttp\Psr7\Request', $request);

    $this->assertEquals('GET', $request->getMethod());
    $this->assertEquals('www.example.com:8080', $request->getUri());
  }

}
