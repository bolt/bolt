/**
 * Holds configuration data that is injected from the application
 *
 * @mixin
 * @namespace Bolt.conf
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {undefined} undefined - Define undefined.
 */
(function (bolt, $, undefined) {
    'use strict';

    /**
     * Configuration data store.
     *
     * @private
     * @type {Object}
     */
    var configData = {};

    /**
     * Fetches the configuration value for the given key.
     *
     * **>>> Use the shortcut alias ``Bolt.conf(key)``! <<<**
     *
     * @example
     *      value = Bolt.conf('foo.bar');
     *
     * @static
     * @function get
     * @memberof Bolt.conf
     *
     * @param {string} key - The key of the value to fetch.
     * @returns {string|number|Object|undefined}conf.get =
     */
    var conf = function (key) {
        var keys = key.split('.'),
            result = configData,
            i;

        for (i = 0; i < keys.length; i++) {
            if (typeof result[keys[i]] !== 'undefined') {
                result = result[keys[i]];
            } else {
                return undefined;
            }
        }

        return result;
    };
    // Set alias function
    conf.get = conf;

    /**
     * Read configuration data from DOM and save it in module
     *
     * @static
     * @function init
     * @memberof Bolt.conf
     */
    conf.init = function () {
        configData = $('script[data-config]').first().data('config');
    };

    // Apply mixin container
    bolt.conf = conf;

})(Bolt || {}, jQuery);
