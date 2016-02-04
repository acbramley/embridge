<?php

/**
 * @file
 * Contains Drupal\Tests\embridge\Unit\EnterMediaDbClientTest
 */

namespace Drupal\Tests\embridge\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\embridge\EnterMediaDbClient;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\RequestException;
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
   * Default options for our request.
   *
   * @var []
   */
  protected $defaultOptions;

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

    $this->defaultOptions = [
      'timeout' => 5,
      'cookies' => new SessionCookieJar('SESSION_STORAGE', TRUE),
    ];
  }

  /**
   * Tests login() success.
   *
   * @covers ::login
   * @test
   */
  public function loginReturnsTrueWhenClientReturns200AndValidXml() {

    $mockResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mockResponse
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.xml', TRUE));

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', $uri, $this->defaultOptions)
      ->willReturn($mockResponse);

    $this->assertTrue($this->emdbClient->login());
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @test
   */
  public function loginReturnsFalseWhenClientReturnsFailedXml() {

    $mockResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mockResponse
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-bad-response.xml', TRUE));

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', $uri, $this->defaultOptions)
      ->willReturn($mockResponse);

    $this->assertFalse($this->emdbClient->login());
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @test
   */
  public function loginThrowsExceptionWhenResponseReturnsNon200Code() {

    $mockResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(403);

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', $uri, $this->defaultOptions)
      ->willReturn($mockResponse);

    $this->setExpectedException('Exception', 'An unexpected response was returned from the Entity Pilot backend');
    $this->emdbClient->login();
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @test
   */
  public function loginThrowsExceptionWhenSendFailsAndResponseIsNull() {

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $method = 'POST';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with($method, $uri, $this->defaultOptions)
      ->willThrowException(new RequestException('', new Request($method, $uri), NULL));

    $this->setExpectedException('Exception', 'Error connecting to EMDB backend');
    $this->emdbClient->login();
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @test
   */
  public function loginThrowsExceptionWhenSendFailsAndResponseCodeIs403() {

    $mockResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockResponse
      ->expects($this->exactly(2))
      ->method('getStatusCode')
      ->willReturn(403);

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $method = 'POST';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with($method, $uri, $this->defaultOptions)
      ->willThrowException(new RequestException('', new Request($method, $uri), $mockResponse));

    $this->setExpectedException('Exception', 'Failed to authenticate with EMDB, please check your settings.');
    $this->emdbClient->login();
  }


  /**
   * Tests upload() success.
   *
   * @covers ::upload
   * @test
   */
  public function uploadReturnsAssetWhenClientReturns200AndValidXml() {
    $mockLoginResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockLoginResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mockLoginResponse
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.xml', TRUE));

    $uri = 'http://www.example.com:8080/media/services/rest/login.xml?catalogid=media&accountname=admin&password=admin';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', $uri, $this->defaultOptions)
      ->willReturn($mockLoginResponse);

    $upload_request = new Request('POST', 'http://www.example.com:8080/media/services/rest/upload.xml');
    $mockUploadResponse = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mockUploadResponse
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);
    $mockUploadResponse
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/upload-expected-good-response.xml', TRUE));

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($upload_request)
      ->willReturn($mockUploadResponse);

    /** @var EmbridgeAssetEntityInterface $asset */
    $asset = EmbridgeAssetEntity::create();
    $expected = [
    ];
    $this->assertEquals($expected, $this->emdbClient->upload($asset));
  }

}
