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
use GuzzleHttp\Psr7\Request;
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
    $mockConfig->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(
        [
          ['uri', $sample_config['uri']],
          ['port', $sample_config['port']],
          ['username', $sample_config['username']],
          ['password', $sample_config['password']],
        ]
      ));

    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configFactory
      ->expects($this->any())
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

  /**
   * Tests login() success.
   *
   * @covers ::login
   * @test
   */
  public function loginReturnsTrueWhenClientReturns200AndValidXml() {

    $request = new Request('POST', 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin');
    $mockResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mockResponse
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.xml', TRUE));

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($request)
      ->willReturn($mockResponse);

    $this->assertTrue($this->emdbClient->login());
  }

}
