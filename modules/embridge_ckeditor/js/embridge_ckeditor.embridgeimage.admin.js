/**
 * @file
 * CKEditor 'embridgeimage' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provides the summary for the "embridgeimage" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "embridgeimage" settings vertical tab.
   */
  Drupal.behaviors.embridgeCkeditorEmbridgeImageSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="embridgeimage"]').drupalSetSummary(function (context) {
        var root = 'input[name="editor[settings][plugins][embridgeimage][embridge_image_upload]';
        var $maxFileSize = $(root + '[max_size]"]');
        var directory = $(root + '[directory]"]').val();

        var maxFileSize = $maxFileSize.val() ? $maxFileSize.val() : $maxFileSize.attr('placeholder');

        var output = Drupal.t("Max size: @size<br> Directory: @directory", {'@size': maxFileSize, '@directory': directory});
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
