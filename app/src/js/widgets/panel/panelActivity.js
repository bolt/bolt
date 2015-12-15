/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Activity panel widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class panelActivity
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseInterval
     *
     * @param {Object} [options] - Options to overide.
     */
    $.widget('bolt.panelActivity', $.bolt.baseInterval, /** @lends jQuery.widget.bolt.panelActivity.prototype */ {
        /**
         * Default options.
         *
         * @property {integer} delay - Initial update delay, shared by all instances
         * @property {string} url - URL to get the latest activity from
         */
        options: {
            delay: 30 * 1000, // 30 seconds
            url: ''
        },

        /**
         * The constructor of the activity panel  widget.
         *
         * @private
         */
        _create: function () {
            // We can set the default only here as because of the call to bolt.conf().
            if (!this.options.url) {
                this.options.url = bolt.conf('paths.async') + 'latestactivity';
            }

            this._super();
        },

        /**
         * Updates panel with latest system activity.
         *
         * @private
         */
        _update: function () {
            var self = this;

            $.get(self.options.url)
                .done(function (data) {
                    var newActivity = $(data);

                    bolt.app.initWidgets(newActivity);
                    self.element.html(newActivity);
                });
        }
    });
})(jQuery, Bolt);
