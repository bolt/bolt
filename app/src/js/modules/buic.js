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
    buic.init = function (context) {
        if (typeof context === 'undefined') {
            context = $(document.documentElement);
        }

        // Widgets initialisations
        $('[data-widget]', context).each(function () {
            $(this)[$(this).data('widget')]()
                .removeAttr('data-widget')
                .removeData('widget');
        });
    };

    // Add placeholder for buic.
    bolt.buic = buic;

})(Bolt || {}, jQuery);
