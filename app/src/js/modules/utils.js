/**
 * Utilities.
 *
 * @mixin
 * @namespace Bolt.utils
 * @deprecated Uses ``eval()`` which makes it a candidate for a cleaner replacement.
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /*
     * Bolt.utils mixin container.
     */
    var utils = {};

    /**
     * Human readable formatted bytes.
     *
     * @static
     * @function init
     * @memberof Bolt.utils
     *
     * @param {number} value - Value to format.
     * @returns {string}
     */
    utils.humanBytes = function (value) {
        var units = ' kMGTPEZY',
            u = -1;

        while (++u < 8 && Math.abs(value) >= 1000) {
            value /= 1000;
        }

        if (typeof Intl === 'object' && Intl && typeof Intl.NumberFormat === 'function') {
            value = value.toLocaleString(
                bolt.conf('locale.long').replace(/_/g, '-'),
                {maximumSignificantDigits: 3}
            );
        } else {
            value = value.toFixed(2);
        }

        return value + ' ' + units[u].trim() + 'B';
    };

    /**
     * Strict parsing of int values.
     *
     * @static
     * @function filterInt
     * @memberof Bolt.utils
     * @see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/parseInt
     *
     * @param {string} value            - Value to check for being int.
     * @param {number} [defaultInt=NaN] - Default value to return when the tested value does not match.
     * @returns {number}
     */
    utils.filterInt = function (value, defaultInt) {
        return /^(\-|\+)?([0-9]+|Infinity)$/.test(value) ? Number(value) : defaultInt || NaN;
    };

    /**
     * Returns a function, that, as long as it continues to be invoked, will not
     * be triggered. The function will be called after it stops being called for
     * N milliseconds. If `immediate` is passed, trigger the function on the
     * leading edge, instead of the trailing.
     *
     * @static
     * @function debounce
     * @memberOf Bolt.utils
     *
     * @param {Function} func
     * @param {number} wait milliseconds
     * @param {boolean} [immediate=false]
     * @returns {Function}
     */
    utils.debounce = function (func, wait, immediate) {
        var timeout;
        return function () {
            var context = this,
                args = arguments;
            var later = function () {
                timeout = null;
                if (!immediate) {
                    func.apply(context, args);
                }
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) {
                func.apply(context, args);
            }
        };
    };

    // Apply mixin container
    bolt.utils = utils;

})(Bolt || {});
