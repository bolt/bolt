/**
 * Handling of Markdown fields.
 *
 * @mixin
 * @namespace Bolt.fields.markdown
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} uiKit - UIkit.
 */
(function (bolt, $, uiKit) {
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
     */
    markdown.init = function (fieldset) {
        uiKit.$('textarea[data-uk-htmleditor]', fieldset).each(function() {
            var editor = uiKit.$(this);

            if (!editor.data('htmleditor')) {
                uiKit.htmleditor(editor, uiKit.Utils.options(editor.attr('data-uk-htmleditor')));
            }
        });
    };


    // Apply mixin container
    bolt.fields.markdown = markdown;

})(Bolt || {}, jQuery, typeof UIkit !== 'undefined' ? UIkit : undefined);
