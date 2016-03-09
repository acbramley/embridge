<?php
/**
 * @file
 * Contains \Drupal\Tests\embridge_ckeditor\Unit\EmbridgeCkeditorImageDialogTest.
 */

namespace Drupal\Tests\embridge_ckeditor\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormState;
use Drupal\editor\Entity\Editor;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\embridge\Entity\EmbridgeAssetEntity;
use Drupal\embridge\Entity\EmbridgeCatalog;
use Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\Core\Form\FormTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once 'embridge_ckeditor.test_functions.php';

/**
 * Class EmbridgeCkeditorImageDialogTest.
 *
 * @package Drupal\Tests\embridge_ckeditor\Unit
 *
 * @coversDefaultClass \Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog
 */
class EmbridgeCkeditorImageDialogTest extends FormTestBase {

  // A mock filter ID.
  const MOCK_FILTER_ID = 21342342342;
  const MOCK_ASSET_UUID = '71b7fcda-bc37-4c72-931a-84f21fccfd69';
  const MOCK_ASSET_ID = 78931279123987312;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $entityTypeManager;

  /**
   * The mock entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $entityRepository;

  /**
   * Our asset helper.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelper|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $assetHelper;

  /**
   * Mock filter format.
   *
   * @var \Drupal\filter\Entity\FilterFormat|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $mockFilter;

  /**
   * Our form.
   *
   * @var \Drupal\embridge_ckeditor\Form\EmbridgeCkeditorImageDialog.
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)
      ->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    $translation = $this->getStringTranslationStub();
    $this->container->set('string_translation', $translation);
    \Drupal::setContainer($this->container);

    // Mock up a storm.
    $this->entityTypeManager = $this->getMockBuilder(EntityTypeManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->assetHelper = $this->getMockBuilder(EnterMediaAssetHelper::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockFilter = $this->getMockBuilder(FilterFormat::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Initialise our form.
    $this->form = new EmbridgeCkeditorImageDialog(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->assetHelper
    );
  }

  /**
   * Sets up mocks for buildForm() calls.
   */
  private function setUpBuildForm() {
    $this->mockFilter->expects($this->once())
      ->method('id')
      ->willReturn(self::MOCK_FILTER_ID);

    $filter_align = new \stdClass();
    $filter_align->status = 1;

    $this->mockFilter->expects($this->once())
      ->method('filters')
      ->with('filter_align')
      ->willReturn($filter_align);

    $editor_settings['plugins']['embridgeimage']['embridge_image_upload'] = [
      'max_size' => '2 MB',
      'catalog_id' => 'test_catalog',
      'directory' => 'test-directory',
    ];
    $mock_editor = $this->getMockBuilder(Editor::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_editor->expects($this->once())
      ->method('getSettings')
      ->willReturn($editor_settings);

    $mock_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_storage->expects($this->once())
      ->method('load')
      ->with(self::MOCK_FILTER_ID)
      ->willReturn($mock_editor);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('editor')
      ->willReturn($mock_storage);
  }

  /**
   * Tear down.
   */
  protected function tearDown() {
    parent::tearDown();

    \Drupal::setContainer(new ContainerBuilder());
  }

  /**
   * Tests buildForm() with empty element input.
   *
   * @covers ::buildForm()
   *
   * @test
   */
  public function buildFormWithEmptyImageElementReturnsExpectedForm() {
    $this->setUpBuildForm();
    $form = [];
    // This is what the form recevies from ckeditor.
    $user_input['editor_object'] = [
      'src' => '',
      'alt' => '',
      'width' => '',
      'height' => '',
    ];
    // Have to mock this because of:
    // LogicException: Form state caching on GET requests is not allowed.
    /** @var FormState|\PHPUnit_Framework_MockObject_MockObject $form_state */
    $form_state = $this->getMockBuilder(FormState::class)
      ->disableOriginalConstructor()
      ->getMock();
    $form_state->expects($this->once())
      ->method('getUserInput')
      ->willReturn($user_input);
    $form_state->expects($this->once())
      ->method('setCached');
    $form_state->expects($this->once())
      ->method('set')
      ->with('image_element', $user_input['editor_object']);

    $actual = $this->form->buildForm($form, $form_state, $this->mockFilter);
    $expected = file_get_contents('expected/image-dialog-empty-image-expected-build.json', TRUE);

    // Test form builds correctly.
    $this->assertJsonStringEqualsJsonString($expected, Json::encode($actual));
  }


  /**
   * Tests buildForm() with existing element input.
   *
   * @covers ::buildForm()
   *
   * @test
   */
  public function buildFormWithExistingImageElementReturnsExpectedForm() {
    $this->setUpBuildForm();
    $form = [];
    // This is what the form recevies from ckeditor.
    $user_input['editor_object'] = [
      'src' => 'www.example.com/test_catalog/views/modules/asset/downloads/preview/thumb/2016/03/113/test.jpg/thumb.jpg',
      'alt' => 'test image',
      'width' => '100',
      'height' => '100',
      'data-entity-type' => 'embridge_asset_entity',
      'data-entity-uuid' => self::MOCK_ASSET_UUID,
      'data-align' => 'right',
    ];
    // Have to mock this because of:
    // LogicException: Form state caching on GET requests is not allowed.
    /** @var FormState|\PHPUnit_Framework_MockObject_MockObject $form_state */
    $form_state = $this->getMockBuilder(FormState::class)
      ->disableOriginalConstructor()
      ->getMock();
    $form_state->expects($this->once())
      ->method('getUserInput')
      ->willReturn($user_input);
    $form_state->expects($this->once())
      ->method('setCached');
    $form_state->expects($this->once())
      ->method('set')
      ->with('image_element', $user_input['editor_object']);

    $mock_asset = $this->getMockBuilder(EmbridgeAssetEntity::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_asset->expects($this->once())
      ->method('id')
      ->willReturn(self::MOCK_ASSET_ID);

    $this->entityRepository->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('embridge_asset_entity', self::MOCK_ASSET_UUID)
      ->willReturn($mock_asset);

    $actual = $this->form->buildForm($form, $form_state, $this->mockFilter);
    $expected = file_get_contents('expected/image-dialog-existing-image-expected-build.json', TRUE);

    // Test form builds correctly.
    $this->assertJsonStringEqualsJsonString($expected, Json::encode($actual));
  }

  /**
   * Test submitForm with empty fid.
   *
   * @covers ::submitForm
   *
   * @test
   */
  public function submitFormWithEmptyFidDoesNotLoadEntities() {
    $form = [];
    $form_state = new FormState();

    $response = $this->form->submitForm($form, $form_state);

    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $response);
    $commands = $response->getCommands();
    $this->assertNotEmpty($commands);
    $this->assertCount(2, $commands);
    $this->assertEquals('editorDialogSave', $commands[0]['command']);
    $this->assertEmpty($commands[0]['values']);
    $this->assertEquals('closeDialog', $commands[1]['command']);
  }


  /**
   * Test submitForm with an fid, ensures values are set correctly.
   *
   * @covers ::submitForm
   *
   * @test
   */
  public function submitFormWithAndFidLoadsEntitiesAndSetsFormStateValues() {
    $form = [];
    $catalog_id = 'test_catalog';
    $app_id = 'test_application_id';
    $source_url = 'www.example.com/test_application_id/test.jpg';

    $form['asset']['#catalog_id'] = $catalog_id;
    $form_state = new FormState();
    $intial_values = [
      'asset' => [self::MOCK_ASSET_ID],
    ];
    $form_state->setValues($intial_values);

    $mock_asset = $this->getMockBuilder(EmbridgeAssetEntity::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_asset->expects($this->once())
      ->method('uuid')
      ->willReturn(self::MOCK_ASSET_UUID);

    $mock_asset_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_asset_storage->expects($this->once())
      ->method('load')
      ->with(self::MOCK_ASSET_ID)
      ->willReturn($mock_asset);

    $mock_catalog = $this->getMockBuilder(EmbridgeCatalog::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_catalog->expects($this->once())
      ->method('getApplicationId')
      ->willReturn($app_id);

    $mock_catalog_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_catalog_storage->expects($this->once())
      ->method('load')
      ->with($catalog_id)
      ->willReturn($mock_catalog);

    $this->assetHelper->expects($this->once())
      ->method('getAssetConversionUrl')
      ->with($mock_asset, $app_id, 'thumb')
      ->willReturn($source_url);

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValueMap([
        ['embridge_asset_entity', $mock_asset_storage],
        ['embridge_catalog', $mock_catalog_storage],
      ]));

    $this->form->submitForm($form, $form_state);
    $expected_values = [
      'attributes' => [
        'src' => $source_url,
        'data-entity-uuid' => self::MOCK_ASSET_UUID,
        'data-entity-type' => 'embridge_asset_entity',
      ],
    ] + $intial_values;

    $actual_values = $form_state->getValues();
    $this->assertEquals($expected_values, $actual_values);
  }

}
