/**
 * Handling of categories input fields.
 *
 * @mixin
 * @namespace Bolt.fields.categories
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.fields.categories mixin container.
     *
     * @private
     * @type {Object}
     */
    var categories = {};

    /**
     * Bind categories field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.categories
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    categories.init = function (fieldset, fconf) {
        var select = $(fieldset).find('select'),
            selectAll = $(fieldset).find('.select-all'),
            selectNone = $(fieldset).find('.select-none');

        select.select2({
            width: '100%',
            allowClear: true,
            placeholder: {
                id: '',
                text: bolt.data('field.categories.text.placeholder')
            }
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
    bolt.fields.categories = categories;

})(Bolt || {}, jQuery);
