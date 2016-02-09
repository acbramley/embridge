<?php
/**
 * @file
 * Contains Drupal\Tests\UnitTestCase\EnterMediaAssetHelperTest.
 */

namespace Drupal\Tests\embridge\Unit;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\Tests\UnitTestCase;

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
   * @var \Drupal\Core\File\MimeType\MimeTypeGuesser|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mimeGuesser;

  /**
   * Our client.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelper
   */
  protected $emdbHelper;


  public function setUp() {
    parent::setUp();

    $mockConfig = $this->getMockBuilder(ImmutableConfig::class)->disableOriginalConstructor()->getMock();
    $sample_config = [
      'uri' => 'http://www.example.com',
      'username' => 'admin',
      'password' => 'admin',
      'application_id' => 'testapp',
    ];
    // Configure the stub.
    $mockConfig->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap(
        [
          ['uri', $sample_config['uri']],
          ['username', $sample_config['username']],
          ['password', $sample_config['password']],
          ['application_id', $sample_config['application_id']],
        ]
      ));

    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configFactory
      ->expects($this->any())
      ->method('get')
      ->with('embridge.settings')
      ->willReturn($mockConfig);

    $this->entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')->disableOriginalConstructor()->getMock();
    $this->mimeGuesser = $this->getMockBuilder(MimeTypeGuesser::class)->disableOriginalConstructor()->getMock();;

    $this->emdbHelper = new EnterMediaAssetHelper($this->configFactory, $this->entityTypeManager, $this->mimeGuesser);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Tests getAssetConversionUrl().
   *
   * @covers ::getAssetConversionUrl
   * @test
   */
  public function getAssetConversionUrlReturnsExpectedUrl() {
    /** @var EmbridgeAssetEntityInterface|\PHPUnit_Framework_MockObject_MockObject $mockAsset */
    $mockAsset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();
    $mockAsset
      ->expects($this->once())
      ->method('getSourcePath')
      ->willReturn('2016/02/123/cats.png');

    $expected_url = 'http://www.example.com/testapp/views/modules/asset/downloads/preview/thumb/2016/02/123/cats.png/thumb.jpg';
    $this->assertEquals($expected_url, $this->emdbHelper->getAssetConversionUrl($mockAsset, 'thumb'));
  }

  /**
   * Tests searchResultToAsset().
   *
   * @covers ::searchResultToAsset
   * @test
   */
  public function searchResultToAssetReturnsQueryResultWhenOneExists() {

    $mock_search_result = [
      'id' => '123',
      'sourcepath' => 'test/123.png',
      'filesize' => 123456789,
      'name' => '123.png',
    ];
    $mock_id = 456;
    $mockQuery = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');
    $mockQuery
      ->expects($this->once())
      ->method('condition')
      ->with('asset_id', $mock_search_result['id']);
    $mockQuery
      ->expects($this->once())
      ->method('execute')
      ->willReturn(['id' => $mock_id]);

    $mockAsset = $this->getMockBuilder('\Drupal\embridge\EmbridgeAssetEntityInterface')->disableOriginalConstructor()->getMock();

    $mockEntityStorage = $this->getMock(EntityStorageInterface::class);
    $mockEntityStorage
      ->expects($this->once())
      ->method('getQuery')
      ->willReturn($mockQuery);
    $mockEntityStorage
      ->expects($this->once())
      ->method('load')
      ->with($mock_id)
      ->willReturn($mockAsset);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('embridge_asset_entity')
      ->willReturn($mockEntityStorage);

    $this->assertEquals($mockAsset, $this->emdbHelper->searchResultToAsset($mock_search_result));
  }
}