/**
 * Markdown field widget.
 *
 * @param {object} $ - Global jQuery object
 * @param {Object} uiKit - UIkit.
 */
(function ($, uiKit) {
    'use strict';

    /**
     * Markdown field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldMarkdown
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldMarkdown', /** @lends jQuery.widget.bolt.fieldMarkdown.prototype */ {
        /**
         * The constructor of the markdown field widget.
         *
         * @private
         */
        _create: function () {
            uiKit.$('textarea[data-uk-htmleditor]', this.element).each(function () {
                var editor = uiKit.$(this);

                if (!editor.data('htmleditor')) {
                    uiKit.htmleditor(editor, uiKit.Utils.options(editor.attr('data-uk-htmleditor')));
                }
            });
        }
    });
})(jQuery, typeof UIkit !== 'undefined' ? UIkit : undefined);
