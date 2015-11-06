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
 * @param {object} bolt - Global Bolt object
 */
(function ($, bolt) {
    /**
     * Bolt progress bars.
     *
     * @class progress
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.progress', /** @lends jQuery.widget.bolt.progress */ {
        /**
         * The constructor of the progress widget.
         *
         * @private
         */
        _create: function () {
            this.element.addClass('buic-progress');
        },

        /**
         * Set the value of progress bar.
         *
         * @private
         * @param {object} bar - The progress bar to set
         * @param {float} value - A value between 0 and 1.0
         */
        _set: function(bar, value) {
            value = parseFloat(value);
            value = isNaN(value) ? 0 : Math.min(100, Math.max(0, Math.round(value * 100)));

            $(bar).find('.progress-bar')
                .attr('aria-valuenow', value)
                .css('width', value + '%');
        },

        /**
         * Adds a new progress bar to the progress widget.
         *
         * @param {string} label - The label
         * @param {float} value - A value between 0 and 1.0
         */
        add: function (label, value) {
            var newBar = $(bolt.data('buic.progress.bar'));

            // Set the label.
            $(newBar)
                .attr('data-label', label)
                .find('.progress-bar')
                .text(label);

            // Set the value.
            this._set(newBar, value);

            // Add new bar and show container.
            this.element
                .append(newBar)
                .show();

            // Show the new bar.
            $(newBar).show(300);
        }
    });
})(jQuery, Bolt);
