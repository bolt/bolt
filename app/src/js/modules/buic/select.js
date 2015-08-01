/**
 * Handling of BUIC selects.
 *
 * @mixin
 * @namespace Bolt.buic.select
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.buic.select mixin container.
     *
     * @private
     * @type {Object}
     */
    var select = {};

    /**
     * Bind BUIC selects.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.select
     *
     * @param {Object} buic
     */
    select.init = function (buic) {
        var select = $(buic).find('select'),
            buttonAll = $(buic).find('.select-all'),
            buttonNone = $(buic).find('.select-none'),
            setButtonState;

        // Initialize the select-all button.
        buttonAll.prop('title', buttonAll.text().trim());
        buttonAll.on('click', function () {
            select.find('option').prop('selected', true).trigger('change');
            this.blur();
        });

        // Initialize the select-none button.
        buttonNone.prop('title', buttonNone.text().trim());
        buttonNone.on('click', function () {
            select.val(null).trigger('change');
            this.blur();
        });

        // Enable/disable buttons.
        setButtonState = function () {
            var options = select.find('option'),
                count = options.length,
                selected = options.filter(':selected').length,
                empty = select.prop('multiple') ? selected === 0 : select.val() === '';

            buttonAll.prop('disabled', selected === count);
            buttonNone.prop('disabled', empty);
        };

        setButtonState();
        select.on('change', setButtonState);
    };

    // Apply mixin container
    bolt.buic.select = select;

})(Bolt || {}, jQuery);
