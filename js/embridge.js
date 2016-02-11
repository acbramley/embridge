/**
 * @file
 * AJAX commands used by embridge module.
 */

(function ($, Drupal) {

    'use strict';

    Drupal.behaviors.embridge_search_choose_asset = {
        attach: function (context, settings) {
            // Tie click events for choosing an image to clicking the submission button.
            $('.embridge-choose-file').click(function(event) {
                event.preventDefault();
                // Set the result that was chosen.
                var asset_id = $(this).attr('data-asset-id');
                $('[name="result_chosen"]').val(asset_id);

                $('.embridge-ajax-search-submit').trigger('click');
            });
        }
    };

    /**
     * Command to save the contents of an embridge asset search.
     */
    Drupal.AjaxCommands.prototype.embridgeSearchDialogSave = function (ajax, response, status) {
        var delta = response.values.delta;
        var entity_id = response.values.entity_id;

        jQuery('.field--name-field-emdb-test .details-wrapper').append('<input data-drupal-selector="edit-field-emdb-test-' + delta + '-fids" type="hidden" name="field_emdb_test[' + delta + '][fids]" value="' + entity_id + '">');
        jQuery('.field--name-field-emdb-test .details-wrapper').append('<input data-drupal-selector="edit-field-emdb-test-' + delta + '-fids" type="hidden" name="field_emdb_test[' + delta + '][_weight]" value="' + delta + '">');
        jQuery('.field--name-field-emdb-test .details-wrapper').append('<input data-drupal-selector="edit-field-emdb-test-' + delta + '-fids" type="hidden" name="field_emdb_test[' + delta + '][display]" value="1">');
        jQuery('input[name="field_emdb_test_1_upload_button"]').mousedown();
    };

})(jQuery, Drupal);
