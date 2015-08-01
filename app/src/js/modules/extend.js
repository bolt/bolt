/**
 * Extend Bolt.
 *
 * @mixin
 * @namespace Bolt.extend
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /**
     * Bolt.extend mixin container.
     *
     * @private
     * @type {Object}
     */
    var extend = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.extend
     */
    extend.init = function () {
    };

    // Apply mixin container
    bolt.extend = extend;

})(Bolt || {});
