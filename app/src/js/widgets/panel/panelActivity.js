/**
 * Activity panel widget.
 *
 * @param {object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Resource id returned by setInterval().
     *
     * @memberOf jQuery.widget.bolt.panelActivity
     * @static
     * @type integer
     */
    var intervalId = 0;

    /**
     * List of update callbacks.
     *
     * @memberOf jQuery.widget.bolt.panelActivity
     * @static
     * @type object
     */
    var updateList = $.Callbacks();

    /**
     * Activity panel widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class panelActivity
     * @memberOf jQuery.widget.bolt
     * @param {object} [options] - Options to overide.
     */
    $.widget('bolt.panelActivity', /** @lends jQuery.widget.bolt.panelActivity */ {
        /**
         * Default options.
         *
         * @private
         * @property {integer} _interval - Update interval, shared by all instances
         */
        _interval: 0,

        /**
         * The constructor of the moment widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // Set up a interval timer used by all moment widgets, if not already done.
            if (!intervalId) {
                intervalId = setInterval(updateList.fire, this._interval);
            }

            // Set up the displayed value.
            this.set();

            // Add the update function to the callback stack.
            this.fnUpdate = function () {
                self._update();
            };
            updateList.add(this.fnUpdate);
        },

        /**
         * Cleaning up.
         *
         * @private
         */
        _destroy: function () {
            // Remove the update function from the update list.
            updateList.remove(this.fnUpdate);

            // Remove the interval timer if that was the last moment.
            if (!updateList.has()) {
                clearInterval(intervalId);
                intervalId = 0;
            }
        },

        /**
         * Updates panel with latest system activity.
         *
         * @private
         */
        _update: function () {
        }
    });
})(jQuery, moment);
