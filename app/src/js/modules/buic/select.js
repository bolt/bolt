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
    };

    // Apply mixin container
    bolt.buic.select = select;

})(Bolt || {}, jQuery);
