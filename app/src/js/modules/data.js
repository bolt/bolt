/*
 * Bolt module: Data
 *
 * Retrieve data segments (strings, templates) that are injected from the application
 *
 * @type {function}
 * @mixin
 */
var BoltData = (function (bolt, $, undefined) {
    /*
     * Bolt module data
     *
     * @private
     * @type {Object}
     */
    var data = {};

    /*
     * Fetches the value for the given key
     * Optinally substitute (string) result
     *
     * @param {string} key - The key of the value to fetch, e.g. 'foo.bar'
     * @param {Object} [subst] - Substitution pairs, e.g.{'%FOO%': 'bar'}
     * @returns {string|number|Object|undefined}
     */
    bolt.data = function (key, subst) {
        var keys = key.split('.'),
            result = data,
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

    /*
     * Read data from DOM and save it in module
     */
    bolt.data.init = function () {
        data = $('script[data-jsdata]').first().data('jsdata') || {};
    };

    return bolt;
})(Bolt || {}, jQuery);
