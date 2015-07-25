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
            buttonNone = $(buic).find('.select-none');

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
    };

    // Apply mixin container
    bolt.buic.select = select;

})(Bolt || {}, jQuery);
