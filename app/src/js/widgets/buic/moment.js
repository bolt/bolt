/**
 * See (http://jquery.com/).
 * @name jQuery
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 */

/**
 * See (http://jquery.com/)
 * @name widget
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 * @memberOf jQuery
 */

/**
 * See (http://jquery.com/)
 * @name bolt
 * @class
 * @memberOf jQuery.widget
 * @param {object} $ - Global jQuery object
 * @param {Object} momentjs - moment.js object
 */
(function ($, momentjs) {
    'use strict';

    /**
     * Resource id returned by setInterval().
     *
     * @memberOf jQuery.widget.bolt.moment
     * @static
     * @type string
     */
    var intervalId = 0;

    /**
     * List of update callbacks.
     *
     * @memberOf jQuery.widget.bolt.moment
     * @static
     * @type string
     */
    var updateList = $.Callbacks();

    /**
     * Bolt moment.
     *
     * @class moment
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.moment', /** @lends jQuery.widget.bolt.moment */ {
        /**
         * The constructor of the moment widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // Set up a interval timer used by all moement widgets, if not already done.
            if (!intervalId) {
                intervalId = setInterval(updateList.fire, 15 * 1000);
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
         * @param {string} [datetime] Datetime to set.
         */
        set: function (datetime) {
            if (datetime) {
                this.element.attr('datetime', momentjs(datetime).format());
            } else {
                datetime = this.element.attr('datetime');
            }
            this.element.attr('title', momentjs(datetime).format('YYYY-MM-DD HH:mm:ss ZZ'));

            this._update();
        }
    });
})(jQuery, moment);
