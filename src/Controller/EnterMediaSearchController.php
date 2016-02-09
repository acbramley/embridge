<?php

/**
 * @file
 * Contains \Drupal\embridge\Controller\EnterMediaSearchController.
 */

namespace Drupal\embridge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embridge\EnterMediaDbClient;

/**
 * Class EnterMediaSearchController.
 *
 * @package Drupal\embridge\Controller
 */
class EnterMediaSearchController extends ControllerBase {

  /**
   * The EMDB client.
   *
   * @var \Drupal\embridge\EnterMediaDbClient
   */
  protected $embridge_client;

  /**
   * {@inheritdoc}
   */
  public function __construct(EnterMediaDbClient $embridge_client) {
    $this->embridge_client = $embridge_client;
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
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function modal() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: hello'),
    ];
  }

}
