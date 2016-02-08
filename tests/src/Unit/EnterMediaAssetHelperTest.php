<?php
/**
 * @file
 * Contains Drupal\Tests\UnitTestCase\EnterMediaAssetHelperTest.
 */

namespace Drupal\Tests\embridge\Unit;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\Tests\UnitTestCase;

class EnterMediaAssetHelperTest extends UnitTestCase {
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


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

    $this->emdbHelper = new EnterMediaAssetHelper($this->configFactory);
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
}