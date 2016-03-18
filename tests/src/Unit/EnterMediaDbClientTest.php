<?php

/**
 * @file
 * Contains Drupal\Tests\embridge\Unit\EnterMediaDbClientTest.
 */

namespace Drupal\Tests\embridge\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystem;
use Drupal\embridge\EnterMediaDbClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Class EnterMediaDbClientTest.
 *
 * @package Drupal\Tests\embridge\Unit
 *
 * @coversDefaultClass \Drupal\embridge\EnterMediaDbClient
 */
class EnterMediaDbClientTest extends UnitTestCase {

  const EXAMPLE_LOGIN_URL = 'http://www.example.com/mediadb/services/authentication/login';
  const EXAMPLE_UPLOAD_URL = 'http://www.example.com/mediadb/services/module/asset/create';
  const EXAMPLE_SEARCH_URL = 'http://www.example.com/mediadb/services/module/asset/search';

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
   * Mock file system.
   *
   * @var \Drupal\Core\File\FileSystem|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fileSystem;


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
   * Default options for our login request.
   *
   * @var []
   */
  protected $defaultLoginOptions;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->client = $this->getMockBuilder(ClientInterface::class)->disableOriginalConstructor()->getMock();
    $this->serializer = new Json();
    $mock_config = $this->getMockBuilder(ImmutableConfig::class)->disableOriginalConstructor()->getMock();
    // Create a map of arguments to return values.
    $sample_config = [
      'uri' => 'http://www.example.com',
      'username' => 'admin',
      'password' => 'admin',
    ];
    $this->sampleConfig = $sample_config;

    // Configure the stub.
    $mock_config->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(
        [
          ['uri', $sample_config['uri']],
          ['username', $sample_config['username']],
          ['password', $sample_config['password']],
        ]
      ));

    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configFactory
      ->expects($this->any())
      ->method('get')
      ->with('embridge.settings')
      ->willReturn($mock_config);

    $this->fileSystem = $this->getMockBuilder(FileSystem::class)->disableOriginalConstructor()->getMock();
    $this->emdbClient = new EnterMediaDbClient($this->configFactory, $this->client, $this->serializer, $this->fileSystem);

    $this->defaultOptions = [
      'timeout' => 100,
      'cookies' => new SessionCookieJar('SESSION_STORAGE', TRUE),
    ];
    $this->defaultLoginOptions = $this->defaultOptions + [
      'json' => ['id' => $this->sampleConfig['username'], 'password' => $this->sampleConfig['password']],
    ];
  }

  /**
   * Tests login() success.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginReturnsTrueWhenClientReturns200AndValidJson() {

    $mock_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_response);

    $this->assertTrue($this->emdbClient->login());
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginReturnsFalseWhenClientReturnsFailedJson() {

    $mock_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-bad-response.json', TRUE));

    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_response);

    $this->assertFalse($this->emdbClient->login());
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginThrowsExceptionWhenResponseReturnsNon200Code() {

    $mock_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_response
      ->expects($this->exactly(2))
      ->method('getStatusCode')
      ->willReturn(403);

    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_response);

    $this->setExpectedExceptionRegExp('Exception', '/Unexpected response: \[\d+] .*/');
    $this->emdbClient->login();
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginThrowsExceptionWhenSendFailsAndResponseIsNull() {

    $method = 'POST';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with($method, self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willThrowException(new RequestException('', new Request($method, self::EXAMPLE_LOGIN_URL), NULL));

    $this->setExpectedExceptionRegExp('Exception', '/Error connecting to EMDB backend: .*/');
    $this->emdbClient->login();
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginThrowsExceptionWhenSendFailsAndResponseCodeIs403() {

    $mock_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_response
      ->expects($this->exactly(2))
      ->method('getStatusCode')
      ->willReturn(403);

    $uri = self::EXAMPLE_LOGIN_URL;
    $method = 'POST';
    $this->client
      ->expects($this->once())
      ->method('request')
      ->with($method, $uri, $this->defaultLoginOptions)
      ->willThrowException(new RequestException('', new Request($method, $uri), $mock_response));

    $this->setExpectedException('Exception', 'Failed to authenticate with EMDB, please check your settings.');
    $this->emdbClient->login();
  }

  /**
   * Tests login() failure.
   *
   * @covers ::login
   * @covers ::doRequest
   *
   * @test
   */
  public function loginRequestsOnlyRunOnceWhenLoginCalledTwice() {

    $mock_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    $this->client
      ->expects($this->once())
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_response);

    $this->assertTrue($this->emdbClient->login());
    // Proves that request is only called once, with subsequent login calls,
    // not a mistake.
    $this->assertTrue($this->emdbClient->login());
  }

  /**
   * Tests upload() success.
   *
   * @covers ::login
   * @covers ::upload
   * @covers ::doRequest
   *
   * @test
   */
  public function uploadUpdatesAssetWhenClientReturns200AndValidJson() {
    $mock_login_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_login_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_login_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    // This sucks, returnValueMap wasn't working though.
    $this->client
      ->expects($this->at(0))
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_login_response);

    $mock_upload_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_upload_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_upload_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/upload-expected-good-response.json', TRUE));

    $expected_realpath = dirname(__FILE__) . '/expected/cat3.png';
    $mock_sourcepath = 'public://test123';

    $this->fileSystem
      ->expects($this->once())
      ->method('realpath')
      ->with($mock_sourcepath)
      ->willReturn($expected_realpath);

    $options = $this->defaultOptions;

    $expected_filename = 'cat3.png';
    $json_request = $this->serializer->encode(
      [
        "id" => NULL,
        "description" => $expected_filename,
      ]
    );
    $body = [
      'multipart' => [
        [
          'name' => 'jsonrequest',
          'contents' => $json_request,
        ],
        [
          'name'     => 'file',
          'contents' => file_get_contents($expected_realpath),
          'filename' => $expected_filename,
        ],
      ],
    ];
    $options += $body;

    $this->client
      ->expects($this->at(1))
      ->method('request')
      ->with('POST', self::EXAMPLE_UPLOAD_URL, $options)
      ->willReturn($mock_upload_response);

    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface|\PHPUnit_Framework_MockObject_MockObject $mock_asset */
    $mock_asset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();
    $mock_asset
      ->expects($this->once())
      ->method('getSourcePath')
      ->willReturn($mock_sourcepath);
    $mock_asset
      ->expects($this->once())
      ->method('getOriginalId')
      ->willReturn(NULL);
    $mock_asset
      ->expects($this->once())
      ->method('getFileName')
      ->willReturn($expected_filename);

    $mock_asset
      ->expects($this->once())
      ->method('setAssetId')
      ->with(456)
      ->will($this->returnSelf());
    $mock_asset
      ->expects($this->once())
      ->method('setSourcePath')
      ->with('2016/02/456/cat3.png')
      ->will($this->returnSelf());

    $this->emdbClient->upload($mock_asset);
  }

  /**
   * Tests upload() metadata sanitisation.
   *
   * @covers ::login
   * @covers ::upload
   * @covers ::doRequest
   *
   * @test
   */
  public function uploadCorrectlySantitisesMetadata() {
    $mock_login_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_login_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_login_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    // This sucks, returnValueMap wasn't working though.
    $this->client
      ->expects($this->at(0))
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_login_response);

    $mock_upload_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_upload_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_upload_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/upload-expected-good-response.json', TRUE));

    $expected_realpath = dirname(__FILE__) . '/expected/cat3.png';
    $mock_sourcepath = 'public://test123';
    $expected_filename = 'cat3.png';

    $unsanitised_metadata = [
      'is_string' => 'abc',
      'is_integer' => 123,
      // Following should be stripped.
      'is_array' => ['a' => 1, 'b' => 2],
      'is_object' => (object) ['a' => 1, 'b' => 2],
      // Should correct to a string, as libraries fails with integer IDs.
      'libraries' => 101,
    ];

    $json_values_expected = [
      'id' => NULL,
      'description' => $expected_filename,
      'is_string' => 'abc',
      'is_integer' => 123,
      'libraries' => '101',
    ];
    $body = [
      'multipart' => [
        [
          'name' => 'jsonrequest',
          'contents' => $this->serializer->encode($json_values_expected),
        ],
        [
          'name'     => 'file',
          'contents' => file_get_contents($expected_realpath),
          'filename' => $expected_filename,
        ],
      ],
    ];
    $options = $this->defaultOptions + $body;

    $this->fileSystem
      ->expects($this->once())
      ->method('realpath')
      ->with($mock_sourcepath)
      ->willReturn($expected_realpath);

    $this->client
      ->expects($this->at(1))
      ->method('request')
      ->with('POST', self::EXAMPLE_UPLOAD_URL, $options)
      ->willReturn($mock_upload_response);

    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface|\PHPUnit_Framework_MockObject_MockObject $mock_asset */
    $mock_asset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();
    $mock_asset
      ->expects($this->once())
      ->method('getSourcePath')
      ->willReturn($mock_sourcepath);
    $mock_asset
      ->expects($this->once())
      ->method('getOriginalId')
      ->willReturn(NULL);
    $mock_asset
      ->expects($this->once())
      ->method('getFileName')
      ->willReturn($expected_filename);

    $this->emdbClient->upload($mock_asset, $unsanitised_metadata);
  }

  /**
   * Tests search() success.
   *
   * @covers ::login
   * @covers ::upload
   * @covers ::doRequest
   *
   * @test
   */
  public function searchReturnsResponseWhenClientReturns200AndValidJson() {
    $mock_login_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_login_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_login_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    // This sucks, returnValueMap wasn't working though.
    $this->client
      ->expects($this->at(0))
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_login_response);

    $mock_search_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_search_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $search_response_body = file_get_contents(
      'expected/search-expected-good-response.json',
      TRUE
    );
    $mock_search_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn($search_response_body);

    $options = $this->defaultOptions;

    $body = [
      'page' => '1',
      'hitsperpage' => '20',
      'showfilters' => "true",
      'query' => [
        'terms' => [],
      ],
    ];
    $options['json'] = $body;

    $this->client
      ->expects($this->at(1))
      ->method('request')
      ->with('POST', self::EXAMPLE_SEARCH_URL, $options)
      ->willReturn($mock_search_response);

    $decoded_body = $this->serializer->decode($search_response_body);
    $this->assertEquals($decoded_body, $this->emdbClient->search());
  }


  /**
   * Tests search() success.
   *
   * @covers ::login
   * @covers ::upload
   * @covers ::doRequest
   *
   * @test
   */
  public function searchCorrectlyPassesParametersToRequest() {
    $mock_login_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_login_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $mock_login_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn(file_get_contents('expected/login-expected-good-response.json', TRUE));

    // This sucks, returnValueMap wasn't working though.
    $this->client
      ->expects($this->at(0))
      ->method('request')
      ->with('POST', self::EXAMPLE_LOGIN_URL, $this->defaultLoginOptions)
      ->willReturn($mock_login_response);

    $mock_search_response = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')->disableOriginalConstructor()->getMock();
    $mock_search_response
      ->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(200);

    $search_response_body = file_get_contents(
      'expected/search-expected-good-response.json',
      TRUE
    );
    $mock_search_response
      ->expects($this->once())
      ->method('getBody')
      ->willReturn($search_response_body);

    $options = $this->defaultOptions;

    $body = [
      'page' => 2,
      'hitsperpage' => 10,
      'showfilters' => "true",
      'query' => [
        'terms' => [
          [
            'field' => 'name',
            'operator' => 'matches',
            'value' => 'test*',
          ],
        ],
      ],
    ];
    $options['json'] = $body;

    $this->client
      ->expects($this->at(1))
      ->method('request')
      ->with('POST', self::EXAMPLE_SEARCH_URL, $options)
      ->willReturn($mock_search_response);

    $decoded_body = $this->serializer->decode($search_response_body);
    $filters = [
      [
        'field' => 'name',
        'operator' => 'matches',
        'value' => 'test*',
      ],
    ];
    $this->assertEquals($decoded_body, $this->emdbClient->search(2, 10, $filters));
  }

}
