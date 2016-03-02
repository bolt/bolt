/**
 * @param {Object} $     - Global jQuery object
 * @param {Object} uiKit - Global UIkit object
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
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldMarkdown', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldMarkdown.prototype */ {
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
