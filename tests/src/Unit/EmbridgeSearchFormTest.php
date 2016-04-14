<?php
/**
 * @file
 * Contains \Drupal\Tests\embridge\Unit\EmbridgeSearchFormTest.
 */

namespace Drupal\Tests\embridge\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Renderer;
use Drupal\embridge\EnterMediaAssetHelper;
use Drupal\embridge\EnterMediaDbClientInterface;
use Drupal\embridge\Form\EmbridgeSearchForm;
use Drupal\Tests\Core\Form\FormTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class EmbridgeSearchFormTest.
 *
 * @package Drupal\Tests\embridge\Unit
 *
 * @group embridge
 *
 * @coversDefaultClass \Drupal\embridge\Form\EmbridgeSearchForm
 */
class EmbridgeSearchFormTest extends FormTestBase {
  const MOCK_FIELD_SETTINGS_FILE_EXTENSIONS = 'txt,pdf,jpeg';
  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Drupal\embridge\EnterMediaDbClient definition.
   *
   * @var \Drupal\embridge\EnterMediaDbClientInterface|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $client;

  /**
   * Our asset helper.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelper|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $assetHelper;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $entityTypeManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $renderer;

  /**
   * Our form.
   *
   * @var \Drupal\embridge\Form\EmbridgeSearchForm.
   */
  protected $form;

  /**
   * Mock field config.
   *
   * @var FieldConfigInterface|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $fieldConfig;

  /**
   * Json decoder.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * Mock assets.
   *
   * @var []
   */
  protected $mockAssets;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    $translation = $this->getStringTranslationStub();
    $this->container->set('string_translation', $translation);
    \Drupal::setContainer($this->container);

    // Mock up a storm.
    $this->client = $this->getMockBuilder(EnterMediaDbClientInterface::class)->disableOriginalConstructor()->getMock();
    $this->assetHelper = $this->getMockBuilder(EnterMediaAssetHelper::class)->disableOriginalConstructor()->getMock();
    $this->entityTypeManager = $this->getMockBuilder(EntityTypeManager::class)->disableOriginalConstructor()->getMock();
    $this->renderer = $this->getMockBuilder(Renderer::class)->disableOriginalConstructor()->getMock();
    $this->fieldConfig = $this->getMockBuilder(FieldConfigInterface::class)->disableOriginalConstructor()->getMock();
    $this->json = new Json();
    $this->mockAssets = [];

    // Initialise our form.
    $this->form = new EmbridgeSearchForm($this->client, $this->assetHelper, $this->entityTypeManager, $this->renderer);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    \Drupal::setContainer(new ContainerBuilder());
  }

  /**
   * Tests buildForm calls what we expect.
   *
   * GIVEN the embridge search form
   * WHEN buildForm is run
   * THEN we should have a form structured as we expect.
   *
   * @covers ::buildForm
   * @covers ::getSearchResults
   * @covers ::formatSearchResults
   *
   * @test
   */
  public function buildFormArrayIsReturnedAsExpected() {
    $form = [];
    $form_state = new FormState();
    $catalog_id = 'test_catalog';
    $application_id = 'test_app';
    $delta = 0;
    // Mock up a whole mess of stuff.
    $this->baseMockBuild($catalog_id, $application_id);

    $build = $this->form->buildForm($form, $form_state, $this->fieldConfig, $delta);
    $expected_build = file_get_contents('expected/embridge-search-form-expected-build.json', TRUE);

    // Assert JSON structures are similar.
    $this->assertJsonStringEqualsJsonString($expected_build, $this->json->encode($build));

    // Test the search results render array is populated correctly.
    // Test this manually as json encoding clobbers the results.
    foreach ($build['search_results']['#results'] as $i => $render_result) {
      $this->assertEquals($this->mockAssets[$i]['asset'], $render_result['#asset']);
    }
  }

  /**
   * Tests buildForm defaults values from user input, passes filters to client.
   *
   * GIVEN the embridge search form
   * WHEN the user has previously entered information
   * THEN fields should be populated with that information.
   *
   * @covers ::buildForm
   *
   * @test
   */
  public function buildFormWithInputIsReturnedAsExpected() {
    $form = [];
    $input = [
      'filename' => 'test',
      'filename_op' => 'matches',
      'result_chosen' => 123,
      'fileformat' => 'jpg',
      'libraries' => 'oceanography',
      'assettype' => 'photo',
      'nonexistantfilter' => 'dontaddme',
    ];
    $form_state = new FormState();
    $form_state->setUserInput($input);

    $catalog_id = 'test_catalog';
    $application_id = 'test_app';
    $delta = 0;

    // Mock filters that search should receive.
    $filters = [
      [
        'field' => 'name',
        'operator' => $input['filename_op'],
        'value' => $input['filename'],
      ],
      [
        'field' => 'libraries',
        'operator' => 'matches',
        'value' => $input['libraries'],
      ],
      [
        'field' => 'assettype',
        'operator' => 'matches',
        'value' => $input['assettype'],
      ],
      [
        'field' => 'fileformat',
        'operator' => 'matches',
        'value' => $input['fileformat'],
      ],
    ];

    // Mock up a whole mess of stuff.
    $this->baseMockBuild($catalog_id, $application_id, $filters);

    $build = $this->form->buildForm($form, $form_state, $this->fieldConfig, $delta);

    $this->assertEquals($input['filename'], $build['filters']['filename']['#default_value']);
    $this->assertEquals($input['filename_op'], $build['filters']['filename_op']['#default_value']);
    $this->assertEquals($input['libraries'], $build['filters']['extra_filters']['libraries']['#default_value']);
    $this->assertEquals($input['assettype'], $build['filters']['extra_filters']['assettype']['#default_value']);
    $this->assertEquals($input['fileformat'], $build['filters']['extra_filters']['fileformat']['#default_value']);
    $this->assertEquals($input['result_chosen'], $build['result_chosen']['#value']);

    // Filters that shouldn't exist.
    $this->assertArrayNotHasKey('nonexistantfilter', $build['filters']);
    $this->assertArrayNotHasKey('editstatus', $build['filters']);
  }

  /**
   * Tests buildForm when the search returns no results.
   *
   * GIVEN the embridge search form
   * WHEN buildForm runs and the API returns no results
   * THEN empty text markup should be displayed.
   *
   * @covers ::buildForm
   *
   * @test
   */
  public function buildFormWithEmptySearchResultsRendersEmptyText() {
    $form = [];
    $form_state = new FormState();
    $catalog_id = 'test_catalog';
    $delta = 0;

    // Mock up specifically for empty results.
    $field_settings = [
      'max_filesize' => '2 MB',
      'file_extensions' => self::MOCK_FIELD_SETTINGS_FILE_EXTENSIONS,
      'catalog_id' => $catalog_id,
    ];
    $field_def = $this->getMockBuilder(FieldDefinitionInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $field_def->expects($this->once())
      ->method('getName')
      ->willReturn('field_test');
    $field_def->expects($this->once())
      ->method('getSettings')
      ->willReturn($field_settings);

    // Search filter always starts with the extensions OR filter.
    $extension_filter_value = str_replace(',', '|', self::MOCK_FIELD_SETTINGS_FILE_EXTENSIONS);
    $extension_filter = [
      'field' => 'fileformat',
      'operator' => 'matches',
      'value' => $extension_filter_value,
    ];
    $filters = [];
    array_unshift($filters, $extension_filter);
    // Client mocking.
    $search_response = $this->json->decode(
      file_get_contents('expected/search-expected-empty-response.json', TRUE)
    );

    $page = 1;
    $per_page = 8;
    $this->client
      ->expects($this->once())
      ->method('search')
      ->with($page, $per_page, $filters)
      ->willReturn($search_response);

    $build = $this->form->buildForm($form, $form_state, $field_def, $delta);

    $this->assertArrayHasKey('#type', $build['search_results']);
    $this->assertEquals('markup', $build['search_results']['#type']);
    $this->assertArrayNotHasKey('#results', $build['search_results']);
    $expected_markup = '<p>No files found, please adjust your filters and try again.</p>';
    $this->assertEquals($expected_markup, $build['search_results']['#markup']);
  }

  /**
   * Tests validateForm when the submit button is pressed.
   *
   * GIVEN the embridge search form
   * WHEN the validateForm function is run
   * THEN no errors should be thrown validation passes.
   *
   * @covers ::validateForm
   *
   * @test
   */
  public function validateWithSubmitButtonPressedThrowsNoErrorForSearchButton() {
    $form = [];
    $form_state = new FormState();
    $triggering_element = [
      '#parents' => ['search'],
    ];
    $form_state->setTriggeringElement($triggering_element);

    $this->form->validateForm($form, $form_state);

    $this->assertEmpty($form_state->getErrors());
  }

  /**
   * Tests validateForm when the submit button is pressed.
   *
   * GIVEN the embridge search form
   * WHEN the validateForm function is run
   * THEN an error should be thrown when the asset chosen doesn't exist.
   *
   * @covers ::validateForm
   *
   * @test
   */
  public function validateWithSubmitButtonPressedThrowsErrorForAssetThatDoesntExists() {
    // Mocking for FormState::setError.
    $form = [
      'search_results' => [
        '#parents' => [],
      ],
    ];
    $form_state = new FormState();
    $triggering_element = [
      '#parents' => ['submit'],
    ];
    $entity_id = 34298734897;
    $input = [
      'result_chosen' => $entity_id,
    ];
    $form_state->setTriggeringElement($triggering_element);
    $form_state->setUserInput($input);

    $mock_asset_storage = $this->getMock(EntityStorageInterface::class);
    $mock_asset_storage
      ->expects($this->once())
      ->method('load')
      ->with($entity_id)
      ->willReturn(NULL);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('embridge_asset_entity')
      ->willReturn($mock_asset_storage);

    $this->form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors);
  }

  /**
   * Tests searchAjax().
   *
   * GIVEN the embridge search form
   * WHEN the ajax handler for the search button is run
   * THEN an ajax response should be returned with the expected commands.
   *
   * @covers ::searchAjax
   * @covers ::ajaxRenderFormAndMessages
   *
   * @test
   */
  public function searchAjaxReturnsAjaxResponse() {
    $form = [
      '#attached' => ['test'],
    ];
    $form_state = new FormState();

    $response = $this->form->searchAjax($form, $form_state);

    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $response);
    $commands = $response->getCommands();
    $this->assertNotEmpty($commands);
    $this->assertCount(3, $commands);
    $this->assertEquals('replaceWith', $commands[0]['method']);
    $this->assertEquals('html', $commands[1]['method']);
    $this->assertEquals('append', $commands[2]['method']);
  }

  /**
   * Tests selectItemAjax().
   *
   * GIVEN the embridge search form
   * WHEN the ajax handler for the select button is run with errors
   * THEN an ajax response should be returned with the expected commands.
   *
   * @covers ::selectItemAjax
   *
   * @test
   */
  public function selectItemAjaxAjaxCallbackReturnsReplaceHtmlWhenErrorsExist() {
    $form = [
      '#attached' => ['test'],
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test', 'test error');

    $response = $this->form->selectItemAjax($form, $form_state);

    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $response);
    $commands = $response->getCommands();
    $this->assertNotEmpty($commands);
    $this->assertCount(3, $commands);
    $this->assertEquals('replaceWith', $commands[0]['method']);
    $this->assertEquals('html', $commands[1]['method']);
    $this->assertEquals('append', $commands[2]['method']);
  }

  /**
   * Tests selectItemAjax().
   *
   * GIVEN the embridge search form
   * WHEN the ajax handler for the select button is run
   * THEN an ajax response should be returned with the expected commands.
   *
   * @covers ::selectItemAjax
   *
   * @test
   */
  public function selectItemAjaxAjaxCallbackReturnsSaveSearchCloseDialogWithNoErrors() {
    $form = [
      '#attached' => ['test'],
    ];
    $mock_entity_id = 123456789;
    $input = [
      'result_chosen' => $mock_entity_id,
    ];
    $form_state = new FormState();
    $form_state->setUserInput($input);

    $response = $this->form->selectItemAjax($form, $form_state);

    $this->assertInstanceOf('\Drupal\Core\Ajax\AjaxResponse', $response);
    $commands = $response->getCommands();
    $this->assertNotEmpty($commands);
    $this->assertCount(2, $commands);
    $this->assertEquals('embridgeSearchDialogSave', $commands[0]['command']);
    $expected_values = [
      'entity_id' => $mock_entity_id,
    ];
    $this->assertEquals($expected_values, $commands[0]['values']);
    $this->assertEquals('closeDialog', $commands[1]['command']);
  }

  /**
   * Sets up all of our services with mock methods so buildForm() is possible.
   *
   * @param string $catalog_id
   *   Catalog id.
   * @param string $application_id
   *   Application id.
   * @param [] $filters
   *   An optional list of filters for the client to receive.
   */
  protected function baseMockBuild(
    $catalog_id,
    $application_id,
    $filters = []
  ) {
    // Field manager mocking.
    $field_settings = [
      'max_filesize' => '2 MB',
      'file_extensions' => self::MOCK_FIELD_SETTINGS_FILE_EXTENSIONS,
      'catalog_id' => $catalog_id,
    ];
    $this->fieldConfig->expects($this->once())
      ->method('getName')
      ->willReturn('field_test');
    $this->fieldConfig->expects($this->once())
      ->method('getSettings')
      ->willReturn($field_settings);

    // Search filter always starts with the extensions OR filter.
    $extension_filter_value = str_replace(',', '|', self::MOCK_FIELD_SETTINGS_FILE_EXTENSIONS);
    $extension_filter = [
      'field' => 'fileformat',
      'operator' => 'matches',
      'value' => $extension_filter_value,
    ];
    array_unshift($filters, $extension_filter);
    // Client mocking.
    $search_response = $this->json->decode(
      file_get_contents('expected/search-expected-small-response.json', TRUE)
    );

    $per_page = 8;
    $page = 1;
    $this->client
      ->expects($this->once())
      ->method('search')
      ->with($page, $per_page, $filters)
      ->willReturn($search_response);

    // Entity type storage mocking.
    $mock_catalog = $this->getMockBuilder(
      '\Drupal\embridge\EmbridgeCatalogInterface'
    )->disableOriginalConstructor()->getMock();
    $mock_catalog
      ->expects($this->once())
      ->method('getApplicationId')
      ->willReturn($application_id);
    $mock_catalog_storage = $this->getMock(EntityStorageInterface::class);
    $mock_catalog_storage
      ->expects($this->once())
      ->method('load')
      ->with($catalog_id)
      ->willReturn($mock_catalog);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('embridge_catalog')
      ->willReturn($mock_catalog_storage);

    // Create mock assets.
    foreach ($search_response['results'] as $i => $result) {
      $mock_asset = $this->getMockBuilder(
        '\Drupal\embridge\EmbridgeAssetEntityInterface'
      )->disableOriginalConstructor()->getMock();
      $mock_asset->expects($this->once())
        ->method('id')
        ->willReturn($i);
      $this->mockAssets[$i]['asset'] = $mock_asset;
      $this->mockAssets[$i]['result'] = $result;
    }
    // Mock up the asset helper.
    $return_map = [];
    foreach ($this->mockAssets as $id => $asset_result) {
      $return_map[] = [
        $asset_result['result'],
        $catalog_id,
        $asset_result['asset'],
      ];
    }
    $this->assetHelper->expects($this->exactly(count($this->mockAssets)))
      ->method('searchResultToAsset')
      ->will($this->returnValueMap($return_map));
  }

  /**
   * Test nextPageSubmit().
   *
   * GIVEN the embridge search form
   * WHEN the submit handler for the next button is run
   * THEN the page variable in storage should be incremented by 1
   * AND the form state should be set to rebuild.
   *
   * @test
   */
  public function nextPageSubmitIncrementsPageInStorage() {
    $storage = [
      'search_results' => [
        'page' => 1,
      ],
    ];
    $form_state = new FormState();
    $form_state->setStorage($storage);
    $form = [];

    $this->form->nextPageSubmit($form, $form_state);

    $this->assertTrue($form_state->isRebuilding());
    $this->assertEquals(2, $form_state->getStorage()['search_results']['page']);
  }

  /**
   * Test previousPageSubmit().
   *
   * GIVEN the embridge search form
   * WHEN the submit handler for the previous button is run
   * THEN the page variable in storage should be decremented by 1
   * AND the form state should be set to rebuild.
   *
   * @test
   */
  public function previousPageSubmitDecrementsPageInStorage() {
    $storage = [
      'search_results' => [
        'page' => 4,
      ],
    ];
    $form_state = new FormState();
    $form_state->setStorage($storage);
    $form = [];

    $this->form->previousPageSubmit($form, $form_state);

    $this->assertTrue($form_state->isRebuilding());
    $this->assertEquals(3, $form_state->getStorage()['search_results']['page']);
  }


  /**
   * Test searchSubmit().
   *
   * GIVEN the embridge search form
   * WHEN the submit handler for the search button is run
   * THEN the page variable in storage should be reset back to 1
   * AND the form state should be set to rebuild.
   *
   * @test
   */
  public function searchSubmitResetsPageInStorage() {
    $storage = [
      'search_results' => [
        'page' => 4,
      ],
    ];
    $form_state = new FormState();
    $form_state->setStorage($storage);
    $form = [];

    $this->form->searchSubmit($form, $form_state);

    $this->assertTrue($form_state->isRebuilding());
    $this->assertEquals(1, $form_state->getStorage()['search_results']['page']);
  }

}
