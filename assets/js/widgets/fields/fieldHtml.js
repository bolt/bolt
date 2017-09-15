/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Html field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldHtml
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldHtml', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldHtml.prototype */ {
        /**
         * The constructor of the html field widget.
         *
         * @private
         */
        _create: function () {
            bolt.ckeditor.add(this.element.find('.ckeditor')[0]);
        }
    });
})(jQuery, Bolt);
