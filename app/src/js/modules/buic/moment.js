/**
 * Handling of BUIC moments.
 *
 * @mixin
 * @namespace Bolt.buic.moment
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.buic.moment mixin container.
     *
     * @private
     * @type {Object}
     */
    var moment = {};

    /**
     * Bind BUIC moments.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.moment
     *
     * @param {Object} buic
     */
    moment.init = function (buic) {
    };

    // Apply mixin container
    bolt.buic.moment = moment;

})(Bolt || {}, jQuery);
