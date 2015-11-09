/**
 * BUIC moment widget.
 *
 * @param {object} $ - Global jQuery object
 * @param {Object} momentjs - moment.js object
 */
(function ($, momentjs) {
    'use strict';

    /**
     * Resource id returned by setInterval().
     *
     * @memberOf jQuery.widget.bolt.buicMoment
     * @static
     * @type integer
     */
    var intervalId = 0;

    /**
     * List of update callbacks.
     *
     * @memberOf jQuery.widget.bolt.buicMoment
     * @static
     * @type object
     */
    var updateList = $.Callbacks();

    /**
     * BUIC moment widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicMoment
     * @memberOf jQuery.widget.bolt
     * @param {object} [options] - Options to overide.
     */
    $.widget('bolt.buicMoment', /** @lends jQuery.widget.bolt.buicMoment */ {
        /**
         * Default options, can be overridden by passing in an object to the constructor with these properties
         * @property {integer} interval - Initial update interval, shared by all instances
         * @property {string} titleFormat - Format string for moment title display
         */
        options: {
            interval: 15 * 1000,
            titleFormat: 'YYYY-MM-DD HH:mm:ss ZZ'
        },

        /**
         * The constructor of the moment widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // Set up a interval timer used by all moment widgets, if not already done.
            if (!intervalId) {
                intervalId = setInterval(updateList.fire, this.options.interval);
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
         * Updates the displayed datetime as relative from now.
         *
         * @private
         */
        _update: function () {
            this.element.html(momentjs(this.element.attr('datetime')).fromNow());
        },

        /**
         * Sets new datetime.
         *
         * @param {string} [datetime] - Datetime to set
         */
        set: function (datetime) {
            if (datetime) {
                this.element.attr('datetime', momentjs(datetime).format());
            } else {
                datetime = this.element.attr('datetime');
            }
            this.element.attr('title', momentjs(datetime).format(this.options.titleFormat));

            this._update();
        }
    });
})(jQuery, moment);
