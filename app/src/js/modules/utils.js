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
     * @param {integer} val - Value to format.
     */
    utils.humanBytes = function (val) {
        var units = ' kMGTPEZY',
            u = -1;

        while (++u < 8 && Math.abs(val) >= 1000) {
            val /= 1000;
        }

        if (!!(typeof Intl === 'object' && Intl && typeof Intl.NumberFormat === 'function')) {
            val = val.toLocaleString(
                bolt.conf('locale.long').replace(/_/g, '-'),
                {maximumSignificantDigits: 3}
            );
        } else {
            val = val.toFixed(2);
        }

        return val + ' ' + units[u].trim() + 'B';
    };

    // Apply mixin container
    bolt.utils = utils;

})(Bolt || {});
