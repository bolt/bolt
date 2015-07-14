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
    };

    // Apply mixin container
    bolt.fields.select = select;

})(Bolt || {}, jQuery);
