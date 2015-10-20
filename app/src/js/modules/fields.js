/**
 * Main mixin for the Bolt module.
 *
 * @mixin
 * @namespace Bolt.fields
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.extend mixin container.
     *
     * @private
     * @type {Object}
     */
    var fields = {};

    /**
     * Initializes the fields, optionally based on a context.
     *
     * @function init
     * @memberof Bolt.fields
     * @param context
     */
    fields.init = function(context) {

        if (typeof context === 'undefined') {
            context = $(document.documentElement);
        }
        // Init fieldsets
        $('[data-bolt-field]', context).each(function () {
            var type = $(this).data('bolt-field'),
                conf = $(this).data('bolt-fconf');

            console.log(type);
            console.log(typeof bolt.fields[type] );
            if (typeof bolt.fields[type] !=='undefined') {
               bolt.fields[type].init(this, conf);
            } else {
                // No custom fieldtype handler
            }
        });
    };



    // Apply mixin container.
    bolt.fields = fields;

})(Bolt || {}, jQuery);
