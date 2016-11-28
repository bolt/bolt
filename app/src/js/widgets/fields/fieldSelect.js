/**
 * @param {object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Select field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldSelect
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldSelect', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldSelect.prototype */ {
        /**
         * Default options.
         *
         * @property {boolean} autocomplete - Use autocomplete on select
         */
        options: {
            autocomplete: false
        },

        /**
         * The constructor of the select field widget.
         *
         * @private
         */
        _create: function () {
            var select = this.element.find('select');
            var options = {
                width: '100%',
                placeholder: {
                    id: '',
                    text: bolt.data('field.select.text.placeholder')
                },
                allowClear: true,
                minimumResultsForSearch: this.options.autocomplete ? 0 : Infinity
            };
            if (this.options.sortable) {
                select.select2Sortable(options);
            } else {
                select.select2(options);
            }
        }
    });
})(jQuery, Bolt);
