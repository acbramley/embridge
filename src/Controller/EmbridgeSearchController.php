<?php

/**
 * @file
 * Contains \Drupal\embridge\Controller\EmbridgeSearchController.
 */

namespace Drupal\embridge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embridge\EnterMediaDbClient;

/**
 * Class EmbridgeSearchController.
 *
 * @package Drupal\embridge\Controller
 */
class EmbridgeSearchController extends ControllerBase {

  /**
   * The EMDB client.
   *
   * @var \Drupal\embridge\EnterMediaDbClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(EnterMediaDbClient $embridge_client) {
    $this->client = $embridge_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('embridge.client')
    );
  }

  /**
   * Renders a modal for our field to use to search the EMDB instance.
   */
  public function modal() {
    return \Drupal::formBuilder()->getForm('Drupal\embridge\Form\EmbridgeSearchForm');
  }

}
