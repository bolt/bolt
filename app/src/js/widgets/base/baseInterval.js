/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Interval definition.
     *
     * @memberOf jQuery.widget.bolt.baseInterval
     * @typedef {Object} Interval
     * @property {integer} id - Resource id returned by setInterval()
     * @property {integer} delay - Update delay
     * @property {Object} callbacks - List of update callbacks
     */

    /**
     * Interval data.
     *
     * @memberOf jQuery.widget.bolt.baseInterval
     * @static
     * @type {Object.<string, Interval>}
     */
    var interval = {};

    /**
     * Base interval widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class baseInterval
     * @memberOf jQuery.widget.bolt
     * @param {object} [options] - Options to overide.
     */
    $.widget('bolt.baseInterval', /** @lends jQuery.widget.bolt.baseInterval.prototype */ {
        /**
         * Default options.
         *
         * @property {integer} delay - Initial update delay, shared by all instances
         */
        options: {
            delay: 10 * 1000 // 10 seconds
        },

        /**
         * The constructor of the base interval widget.
         *
         * @private
         */
        _create: function () {
            var self = this,
                name = this.widgetName;

            // Set up a interval timer used by all activity panel widgets, if not already done.
            if (!interval[name]) {
                var callbacks = $.Callbacks(),
                    id = setInterval(callbacks.fire, this.options.delay);

                interval[name] = {
                    id: id,
                    delay: this.options.delay,
                    callbacks: callbacks
                };
            }

            // Add the update function to the callback stack.
            this._fncUpdate = function () {
                self._update();
            };
            interval[name].callbacks.add(this._fncUpdate);
        },

        /**
         * Cleaning up.
         *
         * @private
         */
        _destroy: function () {
            var name = this.widgetName;

            // Remove the update function from the update list.
            interval[name].callbacks.remove(this._fncUpdate);

            // Remove the interval timer if that was the last moment.
            if (!interval[name].callbacks.has()) {
                clearInterval(interval[name].id);
                delete interval[name];
            }
        }
    });
})(jQuery);
