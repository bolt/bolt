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
    /**
     * Bolt.conf mixin container and configuration store.
     *
     * @private
     * @type {Object}
     */
    var conf = {};

    bolt.conf = data;

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
     * @returns {string|number|Object|undefined}
     */
    conf = conf.get = function (key) {
        var keys = key.split('.'),
            result = conf,
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

    /**
     * Read configuration data from DOM and save it in module
     *
     * @static
     * @function init
     * @memberof Bolt.conf
     */
    conf.init = function () {
        conf = $('script[data-config]').first().data('config');
    };
})(Bolt || {}, jQuery);
