/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC select widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicSelect
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicSelect', /** @lends jQuery.widget.bolt.buicSelect.prototype */ {
        /**
         * The constructor of the select widget.
         *
         * @private
         */
        _create: function () {
            // Private properties
            this.select = this.element.find('select');
            this.buttonAll = this.element.find('.select-all');
            this.buttonNone = this.element.find('.select-none');

            // Initialize the select-all button.
            this.buttonAll.prop('title', this.buttonAll.text().trim());
            this._on(this.buttonAll, {
                'click': function () {
                    this.all();
                }
            });

            // Initialize the select-none button.
            this.buttonNone.prop('title', this.buttonNone.text().trim());
            this._on(this.buttonNone, {
                'click': function () {
                    this.none();
                }
            });

            // Enable/disable buttons.
            this._update();
            this._on(this.select, {
                'change': function () {
                    this._update();
                }
            });
        },

        /**
         * Enable/disable buttons based on selection state.
         *
         * @private
         */
        _update: function () {
            var options = this.select.find('option'),
                count = options.length,
                selected = options.filter(':selected').length,
                empty = this.select.prop('multiple') ? selected === 0 : this.select.val() === '';

            this.buttonAll.prop('disabled', selected === count);
            this.buttonNone.prop('disabled', empty);
        },

        /**
         * Selects all options.
         */
        all: function () {
            this.select
                .find('option')
                .prop('selected', true)
                .trigger('change');
        },

        /**
         * Unselects all options.
         */
        none: function () {
            this.select
                .val(null)
                .trigger('change');
        }
    });
})(jQuery);
