/**
 * @file
 * Embridge Image plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to:
 * - allow for the data-align and data-conversion attributes to be set
 * - change the dialog window opened to use the EmbridgeCkeditorImageDialog
 *
 * @ignore
 */


(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('embridgeimage', {
    requires: 'drupalimage',

    beforeInit: function (editor) {
      // Override the image2 widget definition to handle the additional
      // data-align and data-conversion attributes.
      editor.on('widgetDefinition', function (event) {
        var widgetDefinition = event.data;
        if (widgetDefinition.name !== 'image') {
          return;
        }

        // Override default features definitions for embridgeimage.
        CKEDITOR.tools.extend(widgetDefinition.features, {
          conversion: {
            requiredContent: 'img[data-conversion]'
          },
          align: {
            requiredContent: 'img[data-align]'
          }
        }, true);

        // Extend requiredContent & allowedContent.
        // CKEDITOR.style is an immutable object: we cannot modify its
        // definition to extend requiredContent. Hence we get the definition,
        // modify it, and pass it to a new CKEDITOR.style instance.
        var requiredContent = widgetDefinition.requiredContent.getDefinition();
        requiredContent.attributes['data-align'] = '';
        requiredContent.attributes['data-conversion'] = '';
        widgetDefinition.requiredContent = new CKEDITOR.style(requiredContent);
        widgetDefinition.allowedContent.img.attributes['!data-align'] = true;
        widgetDefinition.allowedContent.img.attributes['!data-conversion'] = true;

        // Override downcast(): ensure we *only* output <img>, but also ensure
        // we include the data-entity-type, data-entity-uuid, data-align and
        // data-conversion attributes.
        var originalDowncast = widgetDefinition.downcast;
        widgetDefinition.downcast = function (element) {
          var img = findElementByName(element, 'img');
          originalDowncast.call(this, img);

          var attrs = img.attributes;

          if (this.data.align !== 'none') {
            attrs['data-align'] = this.data.align;
          }
          attrs['data-conversion'] = this.data.conversion;

          // If img is wrapped with a link, we want to return that link.
          if (img.parent.name === 'a') {
            return img.parent;
          }
          else {
            return img;
          }
        };

        // We want to upcast <img> elements to a DOM structure required by the
        // image2 widget. Depending on a case it may be:
        // We take the same attributes into account as downcast() does.
        var originalUpcast = widgetDefinition.upcast;
        widgetDefinition.upcast = function (element, data) {
          if (element.name !== 'img' || !element.attributes['data-entity-type'] || !element.attributes['data-entity-uuid']) {
            return;
          }
          // Don't initialize on pasted fake objects.
          else if (element.attributes['data-cke-realelement']) {
            return;
          }

          // Parse attributes using originalUpcast adding our custom attributes
          element = originalUpcast.call(this, element, data);
          data['conversion'] = element.attributes['data-conversion'];
          data['align'] = element.attributes['data-align'];

          if (element.parent.name === 'a') {
            element = element.parent;
          }

          return element;
        };

        // Protected; keys of the widget data to be sent to the Drupal dialog.
        // Append to the values defined by the drupalimage plugin.
        // @see core/modules/ckeditor/js/plugins/drupalimage/plugin.js
        CKEDITOR.tools.extend(widgetDefinition._mapDataToDialog, {
          'align': 'data-align',
          'conversion': 'data-conversion'
        });

        // Low priority to ensure drupalimage's event handler runs first.
      }, null, null, 20);
    },

    afterInit: function (editor) {
      var disableButtonIfOnWidget = function (evt) {
        var widget = editor.widgets.focused;
        if (widget && widget.name === 'image') {
          this.setState(CKEDITOR.TRISTATE_DISABLED);
          evt.cancel();
        }
      };
    }
  });

  // Replace the drupalimage's exec command with our custom one.
  CKEDITOR.on('instanceReady', function (event) {
    // Create a new command with the desired exec function
    var editor = event.editor;
    var overridecmd = new CKEDITOR.command(editor, {
      allowedContent: 'img[alt,!src,width,height,!data-entity-type,!data-entity-uuid,!data-align,!data-conversion]',
      requiredContent: 'img[alt,src,width,height,data-entity-type,data-entity-uuid]',
      modes: {wysiwyg: 1},
      canUndo: true,
      exec: function (editor, data) {
        var dialogSettings = {
          title: data.dialogTitle,
          dialogClass: 'embridge-ckeditor-image-dialog'
        };
        Drupal.ckeditor.openDialog(editor, Drupal.url('embridge_ckeditor/dialog/embridge_image/' + editor.config.drupal.format), data.existingValues, data.saveCallback, dialogSettings);
      }
    });

    event.editor.commands.editdrupalimage.exec = overridecmd.exec;
  });


  /**
   * Finds an element by its name.
   *
   * Function will check first the passed element itself and then all its
   * children in DFS order.
   *
   * @param {CKEDITOR.htmlParser.element} element
   *   The element to search.
   * @param {string} name
   *   The element name to search for.
   *
   * @return {?CKEDITOR.htmlParser.element}
   *   The found element, or null.
   */
  function findElementByName(element, name) {
    if (element.name === name) {
      return element;
    }

    var found = null;
    element.forEach(function (el) {
      if (el.name === name) {
        found = el;
        // Stop here.
        return false;
      }
    }, CKEDITOR.NODE_ELEMENT);
    return found;
  }
})(jQuery, Drupal, CKEDITOR);
