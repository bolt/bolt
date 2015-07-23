/**
 * Handling of BUIC checkboxes.
 *
 * @mixin
 * @namespace Bolt.buic.checkbox
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.buic.checkbox mixin container.
     *
     * @private
     * @type {Object}
     */
    var checkbox = {};

    /**
     * Bind BUIC checkboxes.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.checkbox
     *
     * @param {Object} buic
     */
    checkbox.init = function (buic) {
    };

    // Apply mixin container
    bolt.buic.checkbox = checkbox;

})(Bolt || {}, jQuery);
