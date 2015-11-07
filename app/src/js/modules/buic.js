/**
 * Main mixin for the Bolt buic module.
 *
 * @mixin
 * @namespace Bolt.buic
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.buic mixin container.
     *
     * @private
     * @type {Object}
     */
    var buic = {};

    /**
     * Initializes the fields, optionally based on a context.
     *
     * @function init
     * @memberof Bolt.buic
     * @param context
     */
    buic.init = function(context) {

        if (typeof context === 'undefined') {
            context = $(document.documentElement);
        }

        $('.buic-listing', context).each(function () {
            bolt.buic.listing.init(this);
        });
        $('.buic-moment', context).each(function () {
            bolt.buic.moment.init(this);
        });
        // Widgets
        $('.buic-checkbox', context).checkbox();
        $('.buic-progress', context).progress();
        $('.buic-select', context).select();
    };


    // Add placeholder for buic.
    bolt.buic = buic;

})(Bolt || {}, jQuery);
