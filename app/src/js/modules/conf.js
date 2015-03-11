/*
 * Bolt module: Conf
 *
 * @type {function}
 * @mixin
 */
var BoltConf = (function (bolt, $, undefined) {
    /*
     * Bolt module configuration data
     *
     * @private
     * @type {Object}
     */
    var conf = {};

    /*
     * Fetches the configuration value for the given key
     *
     * @param {string} key - The key of the value to fetch, e.g. 'foo.bar'
     * @returns {string|number|Object|undefined}
     */
    bolt.conf = function (key) {
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

    /*
     * Read configuration data from DOM and save it in module
     */
    bolt.conf.init = function () {
        conf = $('script[data-config]').first().data('config');
    };

    return bolt;
})(Bolt || {}, jQuery);
