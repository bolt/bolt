/**
 * See (http://jquery.com/).
 * @name jQuery
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 */

/**
 * See (http://jquery.com/)
 * @name widget
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 * @memberOf jQuery
 */

/**
 * See (http://jquery.com/)
 * @name bolt
 * @class
 * @memberOf jQuery.widget
 * @param {object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Bolt select.
     *
     * @class select
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.select', /** @lends jQuery.widget.bolt.select */ {
        /**
         * The constructor of the select widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // Private properties
            this.select = this.element.find('select');
            this.buttonAll = this.element.find('.select-all');
            this.buttonNone = this.element.find('.select-none');

            // Initialize the select-all button.
            this.buttonAll
                .prop('title', this.buttonAll.text().trim())
                .on('click', function () {
                    self.all();
                    this.blur();
                });

            // Initialize the select-none button.
            this.buttonNone
                .prop('title', this.buttonNone.text().trim())
                .on('click', function () {
                    self.none();
                    this.blur();
                });

            // Enable/disable buttons.
            this._updateButtons();
        },

        /**
         * Enable/disable buttons based on selection state.
         *
         * @private
         */
        _updateButtons: function () {
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
            this.select.find('option').prop('selected', true);
            this._updateButtons();
        },

        /**
         * Unselects all options.
         */
        none: function () {
            this.select.val(null);
            this._updateButtons();
        }
    });
})(jQuery);
