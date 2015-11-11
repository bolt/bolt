/**
 * Activity panel widget.
 *
 * @param {object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module.
 */
(function ($, bolt) {
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
     * Update interval.
     *
     * @memberOf jQuery.widget.bolt.panelActivity
     * @static
     * @type integer
     */
    var interval = 3 * 1000; // 30 seconds

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
         * @property {string} url - URL to get the latest activity from
         */
        options: {
            url: ''
        },

        /**
         * The constructor of the activity panel  widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // We can set the default only here as because of call to bolt.conf().
            if (!this.options.url) {
                this.options.url = bolt.conf('paths.async') + 'latestactivity';
            }

            // Set up a interval timer used by all activity panel widgets, if not already done.
            if (!intervalId) {
                intervalId = setInterval(updateList.fire, interval);
            }

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
            var self = this;

            $.get(
               this.options.url,
               function (data) {
                   var newActivity = $(data);

                   bolt.app.initWidgets(newActivity);
                   self.element.html(newActivity);
               }
            );
        }
    });
})(jQuery, Bolt);
