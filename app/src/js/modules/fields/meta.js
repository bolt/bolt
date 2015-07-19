/**
 * Handling of meta input fields.
 *
 * @mixin
 * @namespace Bolt.fields.meta
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.fields.meta mixin container.
     *
     * @private
     * @type {Object}
     */
    var meta = {};

    /**
     * Bind meta field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.meta
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    meta.init = function (fieldset, fconf) {
        var statusselect = $(fieldset).find('#statusselect');

        statusselect.select2({
            width: '50%',
            minimumResultsForSearch: Infinity
        });
    };

    // Apply mixin container
    bolt.fields.meta = meta;

})(Bolt || {}, jQuery);
