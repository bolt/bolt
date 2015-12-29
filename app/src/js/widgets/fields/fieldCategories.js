/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Categories field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldCategories
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldCategories', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldCategories.prototype */ {
        /**
         * The constructor of the categories field widget.
         *
         * @private
         */
        _create: function () {
            var select = this.element.find('select');

            select.select2({
                width: '100%',
                allowClear: true,
                placeholder: {
                    id: '',
                    text: bolt.data('field.categories.text.placeholder')
                }
            });
        }
    });
})(jQuery, Bolt);
