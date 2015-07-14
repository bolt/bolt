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
            selectAll = $(fieldset).find('button.select-all');

        // Bind select-all button.
        selectAll.on('click', function () {
            select.find('option').prop('selected', true);
        });
    };

    // Apply mixin container
    bolt.fields.categories = categories;

})(Bolt || {}, jQuery);
