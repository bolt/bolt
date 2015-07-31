/**
 * Handling of select input fields.
 *
 * @mixin
 * @namespace Bolt.fields.select
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.select mixin container.
     *
     * @private
     * @type {Object}
     */
    var select = {};

    /**
     * Bind select field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.select
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    select.init = function (fieldset, fconf) {
        var select = $(fieldset).find('select');

        select.select2({
            width: '100%',
            placeholder: {
                id: '',
                text: bolt.data('field.select.text.placeholder')
            },
            allowClear: true,
            minimumResultsForSearch: fconf.autocomplete ? 0 : Infinity
        });
    };

    // Apply mixin container
    bolt.fields.select = select;

})(Bolt || {}, jQuery);
