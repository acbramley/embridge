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

    // Initialise our form.
    $this->form = new EmbridgeCkeditorImageDialog(
      $this->entityTypeManager,
      $this->entityRepository,
      $this->assetHelper
    );
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

}
