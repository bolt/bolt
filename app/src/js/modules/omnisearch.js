/**
 * Omnisearch initialisation.
 *
 * @mixin
 * @namespace Bolt.omnisearch
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Bolt.omnisearch mixin container.
     *
     * @private
     * @type {Object}
     */
    var omnisearch = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.omnisearch
     */
    omnisearch.init = function () {
    };

    // Apply mixin container.
    bolt.omnisearch = omnisearch;

})(Bolt || {}, jQuery);
