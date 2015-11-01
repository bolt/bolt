/**
 * Filebrowser functionality.
 *
 * @mixin
 * @namespace Bolt.filebrowser
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.filebrowser mixin container.
     *
     * @private
     * @type {Object}
     */
    var filebrowser = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.filebrowser
     */
    filebrowser.init = function () {
    };

    // Apply mixin container
    bolt.filebrowser = filebrowser;

})(Bolt || {}, jQuery);
