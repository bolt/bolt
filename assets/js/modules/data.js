/**
 * Holds data segments (strings, templates) that are injected from the application.
 *
 * @mixin
 * @namespace Bolt.data
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {undefined} undefined - Define undefined.
 */
(function (bolt, $, undefined) {
    'use strict';

    /**
     * Application data store.
     *
     * @private
     * @type {Object}
     */
    var appData = {};

    /**
     * Fetches the value for the given key.
     *
     * **>>> Use the shortcut alias ``Bolt.data(key, subst)``! <<<**
     *
     * Optionally substitutes the result with given sustitution values if the result is of type string.
     *
     * @example
     *      value = Bolt.data('foo.bar');
     *      value = Bolt.data('foo.bar', {'%FOO%': 'foo', '%BAR%': 'bar'});
     *
     * @static
     * @function get
     * @memberof Bolt.data
     *
     * @param {string} key - The key of the value to fetch.
     * @param {Object} [subst] - Substitution pairs.
     * @returns {string|number|Object|undefined}
     */
    var data = function (key, subst) {
        var keys = key.split('.'),
            result = appData,
            i;

        for (i = 0; i < keys.length; i++) {
            if (typeof result[keys[i]] !== 'undefined') {
                result = result[keys[i]];
            } else {
                return undefined;
            }
        }

        if (subst && typeof result === 'string') {
            return result.subst(subst);
        } else {
            return result;
        }
    };
    // Set alias function
    data.get = data;

    /**
     * Initializes the mixin by reading data from DOM and saving it.
     *
     * @static
     * @function init
     * @memberof Bolt.data
     */
    data.init = function () {
        appData = $('script[data-jsdata]').first().data('jsdata') || {};
    };

    // Apply mixin container
    bolt.data = data;

})(Bolt || {}, jQuery);
