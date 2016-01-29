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
use GuzzleHttp\Psr7\Uri;

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
   * Mock config.
   *
   * @var []
   */
  protected $sampleConfig;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->client = $this->getMock(ClientInterface::class);
    $this->serializer = new Json();
    $mockConfig = $this->getMockBuilder(ImmutableConfig::class)->disableOriginalConstructor()->getMock();
    // Create a map of arguments to return values.
    $sample_config = [
      'uri' => 'http://www.example.com',
      'port' => '8080',
      'username' => 'admin',
      'password' => 'admin',
    ];
    $this->sampleConfig = $sample_config;

    // Configure the stub.
    $mockConfig->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap(
        [
          ['uri', $sample_config['uri']],
          ['port', $sample_config['port']],
        ]
      ));

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

    $expected_uri = new Uri($this->sampleConfig['uri'] . ':' . $this->sampleConfig['port'] . '/');
    $this->assertEquals('POST', $request->getMethod());
    $this->assertEquals($expected_uri, $request->getUri());
  }


  /**
   * Tests initRequest() with parameter.
   *
   * @covers ::initRequest
   * @test
   */
  public function initRequestWithPathReturnsRequestObjectPopulatedWithConfig() {
    $request = $this->emdbClient->initRequest('pleaseandthankyou');
    $this->assertInstanceOf('\GuzzleHttp\Psr7\Request', $request);

    $expected_uri = new Uri($this->sampleConfig['uri'] . ':' . $this->sampleConfig['port'] . '/pleaseandthankyou');
    $this->assertEquals($expected_uri, $request->getUri());
  }

}
