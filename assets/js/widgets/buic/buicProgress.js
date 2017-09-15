/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Template of the progress bar
     *
     * @memberOf jQuery.widget.bolt.buicProgress
     * @static
     * @type string
     */
    var barTemplate =
        '<div class="progress">' +
            '<div class="progress-bar progress-bar-striped active"' +
                ' role="progressbar"' +
                ' aria-valuemin="0"' +
                ' aria-valuemax="100">' +
            '</div>' +
        '</div>';

    /**
     * BUIC progress widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicProgress
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicProgress', /** @lends jQuery.widget.bolt.buicProgress.prototype */ {
        /**
         * The constructor of the progress widget.
         *
         * @private
         */
        _create: function () {
            // Private properties
            this.bars = {};

            this.element.addClass('buic-progress');
        },

        /**
         * Set the value of progress bar.
         *
         * @private
         * @param {integer} id - The id of the progress bar to set
         * @param {float} value - A value between 0 and 1.0
         */
        _set: function (id, value) {
            value = parseFloat(value);
            value = isNaN(value) ? 0 : Math.min(100, Math.max(0, Math.round(value * 100)));

            if (this.bars[id]) {
                this.bars[id]
                    .find('.progress-bar')
                    .attr('aria-valuenow', value)
                    .css('width', value + '%');
            }
        },

        /**
         * Adds a new progress bar to the progress widget, if one with the id doesn't already exists.
         *
         * @param {string} id - The progress bar id
         * @param {float} value - A value between 0 and 1.0
         * @param {string} [label] - The label. If not given the id is used as Label.
         */
        add: function (id, value, label) {
            if (!this.bars[id]) {
                // Create the new bar from the template.
                this.bars[id] = $(barTemplate);

                // Set its label.
                this.bars[id]
                    .attr('data-label', label || id)
                    .find('.progress-bar')
                    .text(label || id);

                // Set the value of the bar.
                this._set(id, value);

                // Add the new bar to the container and show the container.
                this.element
                    .append(this.bars[id])
                    .show();

                // Show the new bar.
                this.bars[id].show(300);
            }
        },

        /**
         * Removes the progress bar with the id from the progress bar container.
         *
         * @param {string} id - The progress bar id
         */
        remove: function (id) {
            if (this.bars[id]) {
                var self = this,
                    bar = this.bars[id];

                // Remove the bar from the internal list.
                delete this.bars[id];

                // Remove the bar from the progress container.
                bar.hide(300, function () {
                    bar.remove();

                    // Hide the container when last bar was removed.
                    if (Object.keys(self.bars).length === 0) {
                        self.element.hide();
                    }
                });
            }
        },

        /**
         * Sets the progress bar value.
         *
         * @param {string} id - The progress bar id
         * @param {float} value - A value between 0 and 1.0
         */
        set: function (id, value) {
            this._set(id, value);
        }
    });
})(jQuery);
