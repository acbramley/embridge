<?php
/**
 * @file
 * Contains Drupal\Tests\UnitTestCase\EnterMediaAssetHelperTest.
 */

namespace Drupal\Tests\embridge\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Class EnterMediaAssetHelperTest.
 *
 * @package Drupal\Tests\embridge\Unit
 */
class EnterMediaAssetHelperTest extends UnitTestCase {
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;


  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * Mime type guesser service.
   *
   * @var \Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mimeGuesser;

  /**
   * Our client.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelper
   */
  protected $emdbHelper;

  /**
   * SetUp().
   */
  public function setUp() {
    parent::setUp();

    $mock_config = $this->getMockBuilder(ImmutableConfig::class)->disableOriginalConstructor()->getMock();
    $sample_config = [
      'uri' => 'http://www.example.com',
      'username' => 'admin',
      'password' => 'admin',
    ];
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

    $this->entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')->disableOriginalConstructor()->getMock();
    $this->mimeGuesser = $this->getMockBuilder(MimeTypeGuesser::class)->disableOriginalConstructor()->getMock();;

    $this->emdbHelper = new EnterMediaAssetHelper($this->configFactory, $this->entityTypeManager, $this->mimeGuesser);
  }

  /**
   * Tear down.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Tests getAssetConversionUrl().
   *
   * @covers ::getAssetConversionUrl
   *
   * @test
   */
  public function getAssetConversionUrlReturnsExpectedUrl() {
    /** @var \Drupal\embridge\EmbridgeAssetEntityInterface|\PHPUnit_Framework_MockObject_MockObject $mock_asset */
    $mock_asset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();
    $mock_asset
      ->expects($this->once())
      ->method('getSourcePath')
      ->willReturn('2016/02/123/cats.png');

    $expected_url = 'http://www.example.com/testapp/views/modules/asset/downloads/preview/thumb/2016/02/123/cats.png/thumb.jpg';
    $this->assertEquals($expected_url, $this->emdbHelper->getAssetConversionUrl($mock_asset, 'testapp', 'thumb'));
  }

  /**
   * Tests searchResultToAsset().
   *
   * @covers ::searchResultToAsset
   * @covers ::assetFromAssetId
   *
   * @test
   */
  public function searchResultToAssetReturnsQueryResultWhenOneExists() {

    $mock_search_result = [
      'id' => '123',
      'sourcepath' => 'test/123.png',
      'filesize' => 123456789,
      'name' => '123.png',
    ];
    $mock_id = '456';
    $mock_query = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');
    $mock_query
      ->expects($this->once())
      ->method('condition')
      ->with('asset_id', $mock_search_result['id']);
    $mock_query
      ->expects($this->once())
      ->method('execute')
      ->willReturn([$mock_id => $mock_id]);

    $mock_asset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();

    $mock_entity_storage = $this->getMock(EntityStorageInterface::class);
    $mock_entity_storage
      ->expects($this->once())
      ->method('getQuery')
      ->willReturn($mock_query);
    $mock_entity_storage
      ->expects($this->once())
      ->method('load')
      ->with($mock_id)
      ->willReturn($mock_asset);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('embridge_asset_entity')
      ->willReturn($mock_entity_storage);

    $this->assertEquals($mock_asset, $this->emdbHelper->searchResultToAsset($mock_search_result, 'testcatalog'));
  }


  /**
   * Tests searchResultToAsset().
   *
   * @covers ::searchResultToAsset
   * @covers ::assetFromAssetId
   *
   * @test
   */
  public function searchResultToAssetReturnsNewAssetWhenNoneExist() {

    $mock_search_result = [
      'id' => '123',
      'sourcepath' => 'test/123.png',
      'filesize' => 123456789,
      'name' => '123.png',
    ];
    $mock_query = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');
    $mock_query
      ->expects($this->once())
      ->method('condition')
      ->with('asset_id', $mock_search_result['id']);
    $mock_query
      ->expects($this->once())
      ->method('execute')
      ->willReturn([]);

    $mock_asset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();
    $mock_asset
      ->expects($this->once())
      ->method('setTemporary')
      ->willReturn($this->returnSelf());
    $mock_asset
      ->expects($this->once())
      ->method('save');

    $expected_mime = 'image/png';
    $this->mimeGuesser
      ->expects($this->once())
      ->method('guess')
      ->with($mock_search_result['name'])
      ->willReturn($expected_mime);
    $expected_values = [
      'asset_id' => $mock_search_result['id'],
      'source_path' => $mock_search_result['sourcepath'],
      'filesize' => $mock_search_result['filesize'],
      'filename' => $mock_search_result['name'],
      'filemime' => $expected_mime,
      'catalog_id' => 'testcatalog',
    ];
    $mock_entity_storage = $this->getMock(EntityStorageInterface::class);
    $mock_entity_storage
      ->expects($this->once())
      ->method('getQuery')
      ->willReturn($mock_query);
    $mock_entity_storage
      ->expects($this->once())
      ->method('create')
      ->with($expected_values)
      ->willReturn($mock_asset);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('embridge_asset_entity')
      ->willReturn($mock_entity_storage);

    $this->assertEquals($mock_asset, $this->emdbHelper->searchResultToAsset($mock_search_result, 'testcatalog'));
  }

}
