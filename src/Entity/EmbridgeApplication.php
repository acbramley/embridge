<?php

/**
 * @file
 * Contains \Drupal\embridge\Entity\EmbridgeApplication.
 */

namespace Drupal\embridge\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\embridge\EmbridgeApplicationInterface;

/**
 * Defines the EMBridge Application entity.
 *
 * @ConfigEntityType(
 *   id = "embridge_application",
 *   label = @Translation("EMBridge Application"),
 *   handlers = {
 *     "list_builder" = "Drupal\embridge\EmbridgeApplicationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\embridge\Form\EmbridgeApplicationForm",
 *       "edit" = "Drupal\embridge\Form\EmbridgeApplicationForm",
 *       "delete" = "Drupal\embridge\Form\EmbridgeApplicationDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\embridge\EmbridgeApplicationHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "embridge_application",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/embridge/embridgesettings/applications/embridge_application/{embridge_application}",
 *     "add-form" = "/admin/config/embridge/embridgesettings/applications/embridge_application/add",
 *     "edit-form" = "/admin/config/embridge/embridgesettings/applications/embridge_application/{embridge_application}/edit",
 *     "delete-form" = "/admin/config/embridge/embridgesettings/applications/embridge_application/{embridge_application}/delete",
 *     "collection" = "/admin/config/embridge/embridgesettings/applications/embridge_application"
 *   }
 * )
 */
class EmbridgeApplication extends ConfigEntityBase implements EmbridgeApplicationInterface {
  /**
   * The EMBridge Application ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The EMBridge Application label.
   *
   * @var string
   */
  protected $label;

}
