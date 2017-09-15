/**
 * @param {Object} $        - Global jQuery object
 * @param {Object} momentjs - Global moment.js object
 */
(function ($, momentjs) {
    'use strict';

    /**
     * BUIC moment widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicMoment
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseInterval
     *
     * @param {Object} [options] - Options to overide
     */
    $.widget('bolt.buicMoment', $.bolt.baseInterval, /** @lends jQuery.widget.bolt.buicMoment.prototype */ {
        /**
         * Default options.
         *
         * @property {integer} delay - Initial update delay, shared by all instances
         * @property {string} titleFormat - Format string for moment title display
         */
        options: {
            delay: 15 * 1000,
            titleFormat: 'YYYY-MM-DD HH:mm:ss ZZ'
        },

        /**
         * The constructor of the moment widget.
         *
         * @private
         */
        _create: function () {
            // Set up the displayed value.
            this.set();

            this._super();
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
         * Sets new date-time and display it.
         *
         * @param {string} [datetime] - A valid date-time as defined in RFC 3339 to set
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
