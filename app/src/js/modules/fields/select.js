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
            selectAll = $(fieldset).find('.select-all'),
            selectNone = $(fieldset).find('.select-none');

        select.select2({
            width: '100%',
            placeholder: bolt.data('field.select.text.placeholder'),
            allowClear: true,
            minimumResultsForSearch: fconf.autocomplete ? 0 : Infinity
        });

        // Initialize the select-all button.
        selectAll.prop('title', selectAll.text().trim());
        selectAll.on('click', function () {
            select.find('option').prop('selected', true).trigger('change');
            this.blur();
        });

        // Initialize the select-none button.
        selectNone.prop('title', selectNone.text().trim());
        selectNone.on('click', function () {
            select.val(null).trigger('change');
            this.blur();
        });
    };

    // Apply mixin container
    bolt.fields.select = select;

})(Bolt || {}, jQuery);
