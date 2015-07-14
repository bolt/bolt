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
        var select = $(fieldset).find('select'),
            selectAll = $(fieldset).find('button.select-all');

        if (fconf.autocomplete) {
            select.select2({
                placeholder: bolt.data('field.select.text.placeholder'),
                allowClear: true
            });
        }

        // Bind select-all button.
        selectAll.on('click', function () {
            select.find('option').prop('selected', true);
        });
    };

    // Apply mixin container
    bolt.fields.select = select;

})(Bolt || {}, jQuery);
