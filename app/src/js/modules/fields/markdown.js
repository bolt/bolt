/**
 * Handling of Markdown fields.
 *
 * @mixin
 * @namespace Bolt.fields.markdown
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, UI) {
    'use strict';

    /**
     * Bolt.fields.markdown mixin container.
     *
     * @private
     * @type {Object}
     */
    var markdown = {};

    /**
     * Bind text field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.markdown
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    markdown.init = function (fieldset) {

        UI.$('textarea[data-uk-htmleditor]', fieldset).each(function() {

            var editor = UI.$(this);

            if (!editor.data('htmleditor')) {
                UI.htmleditor(editor, UI.Utils.options(editor.attr('data-uk-htmleditor')));
            }
        });
    };


    // Apply mixin container
    bolt.fields.markdown = markdown;

})(Bolt || {}, typeof UIkit !== 'undefined' ? UIkit : undefined);
