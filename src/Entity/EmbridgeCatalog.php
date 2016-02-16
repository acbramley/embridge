<?php

/**
 * @file
 * Contains \Drupal\embridge\Entity\EmbridgeCatalog.
 */

namespace Drupal\embridge\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\embridge\EmbridgeCatalogInterface;

/**
 * Defines the EMBridge Catalog entity.
 *
 * @ConfigEntityType(
 *   id = "embridge_catalog",
 *   label = @Translation("EMBridge Catalog"),
 *   handlers = {
 *     "list_builder" = "Drupal\embridge\EmbridgeCatalogListBuilder",
 *     "form" = {
 *       "add" = "Drupal\embridge\Form\EmbridgeCatalogForm",
 *       "edit" = "Drupal\embridge\Form\EmbridgeCatalogForm",
 *       "delete" = "Drupal\embridge\Form\EmbridgeCatalogDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\embridge\EmbridgeCatalogHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "embridge_catalog",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/embridge/embridgesettings/catalogs/embridge_catalog/{embridge_catalog}",
 *     "add-form" = "/admin/config/embridge/embridgesettings/catalogs/embridge_catalog/add",
 *     "edit-form" = "/admin/config/embridge/embridgesettings/catalogs/embridge_catalog/{embridge_catalog}/edit",
 *     "delete-form" = "/admin/config/embridge/embridgesettings/catalogs/embridge_catalog/{embridge_catalog}/delete",
 *     "collection" = "/admin/config/embridge/embridgesettings/catalogs/embridge_catalog"
 *   }
 * )
 */
class EmbridgeCatalog extends ConfigEntityBase implements EmbridgeCatalogInterface {
  /**
   * The EMBridge Catalog ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The EMBridge Catalog label.
   *
   * @var string
   */
  protected $label;

  /**
   * The EMDB Catalog Application ID in EnterMedia.
   *
   * @var string
   */
  protected $applicationId;

  /**
   * A newline separated list of conversions.
   *
   * @var string
   */
  protected $conversions;

  /**
   * {@inheritdoc}
   */
  public function getApplicationId() {
    return $this->applicationId;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversions() {
    return $this->conversions;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversionsArray() {
    $conversions = $this->getConversions();
    $return = explode("\r\n", $conversions);

    return $return;
  }

}
