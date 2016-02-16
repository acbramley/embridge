/**
 * @file
 * AJAX commands used by embridge module.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.embridge_search_submit = {
    attach: function (context, settings) {
      $('.embridge-search-form input[name="filename"]').on('keypress', function (event) {
        if (event.keyCode == 13) {
          $('.embridge-ajax-search-submit').trigger('mousedown');
        }
      });
    }
  };

  Drupal.behaviors.embridge_search_choose_asset = {
    attach: function (context, settings) {
      // Tie click events for choosing an image to clicking the submission button.
      $('.embridge-choose-file').on('click', function (event) {
        event.preventDefault();
        // Set the result that was chosen.
        var asset_id = $(this).attr('data-asset-entity-id');
        $('[name="result_chosen"]').val(asset_id);

        $('.embridge-ajax-select-file').trigger('click');
      });
    }
  };

  /**
   * Command to save the contents of an embridge asset search.
   */
  Drupal.AjaxCommands.prototype.embridgeSearchDialogSave = function (ajax, response, status) {
    var delta = response.values.delta;
    var entity_id = response.values.entity_id;
    var field_name = response.values.field_name;
    var field_id_dashes = field_name.replace(/_/g, '-');

    var field_wrapper = '.field--name-' + field_id_dashes + ' .details-wrapper';
    var data_selector = 'edit-' + field_id_dashes + '-' + delta + '-fids';

    // Create inputs as if a file had been added to the form.
    jQuery(field_wrapper).append('<input data-drupal-selector="' + data_selector + '" type="hidden" name="' + field_name + '[' + delta + '][fids]" value="' + entity_id + '">');
    jQuery(field_wrapper).append('<input data-drupal-selector="' + data_selector + '" type="hidden" name="' + field_name + '[' + delta + '][_weight]" value="' + delta + '">');
    jQuery(field_wrapper).append('<input data-drupal-selector="' + data_selector + '" type="hidden" name="' + field_name + '[' + delta + '][display]" value="1">');
    // Trigger an "upload" of the asset.
    jQuery('input[name="' + field_name + '_' + delta + '_upload_button"]').trigger('mousedown');
  };

})(jQuery, Drupal);
