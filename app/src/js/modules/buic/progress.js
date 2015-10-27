/**
 * Handling of BUIC progress bars.
 *
 * @mixin
 * @namespace Bolt.buic.progress
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.buic.progress mixin container.
     *
     * @private
     * @type {Object}
     */
    var progress = {};

    /**
     * Bind BUIC progress bars.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.progress
     *
     * @param {Object} buic
     */
    progress.init = function (buic) {
    };

    // Apply mixin container
    bolt.buic.progress = progress;

})(Bolt || {}, jQuery);
