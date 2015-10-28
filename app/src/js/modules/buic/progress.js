/**
 * Handling of BUIC progress bars.
 *
 * @mixin
 * @namespace Bolt.buic.progress
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.buic.progress mixin container.
     *
     * @private
     * @type {Object}
     */
    var progress = {};

    /**
     * Bind BUIC progress bars.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.progress
     *
     * @param {Object} buic
     */
    progress.init = function (buic) {
        // Adds a new progress bar to the progress bar container.
        $(buic).on('buic:progress-add', function (event, label, value) {
            add(buic, label || '', value || 0);
        });
        // Remove progress bar from progress bar container.
        $(buic).on('buic:progress-remove', function (event, label) {
            remove(buic, label || '');
        });
        // Set value.
        $(buic).on('buic:progress-set', function (event, label, value) {
            $(buic).children().each(function () {
                if ($(this).data('label') === label) {
                    setValue(this, value || 0);
                }
            });

        });
    };

    /**
     * Adds a new progress bar to the progress bar container.
     *
     * @private
     * @function add
     * @memberof Bolt.buic.progress
     *
     * @param {Object} progress - The progress bar container to add to.
     * @param {string} label - The label.
     * @param {float} value - A float between 0 and 1.
     */
    function add(progress, label, value) {
        var bar = $(bolt.data('buic.progress.bar'));

        // Set the label.
        $(bar)
            .attr('data-label', label)
            .find('.progress-bar')
            .text(label);

        // Set the value.
        setValue(bar, value);

        // Add new bar and show container.
        $(progress)
            .append(bar)
            .show();
        $(bar).show(300);
    }

    /**
     * Remove progress bar from progress bar container.
     *
     * @private
     * @function remove
     * @memberof Bolt.buic.progress
     *
     * @param {Object} progress - The progress bar container to add to.
     * @param {string} label - The label.
     */
    function remove(progress, label) {
        // Loops through all bars and remove all with the same label.
        $(progress).children().each(function (n, bar) {
            if ($(bar).data('label') === label) {
                $(bar).hide(300, function () {
                    $(bar).remove();
                });
            }
        });
        // Hide the container when last bar was removed.
        if ($(progress).children().length === 0) {
            $(progress).hide();
        }
    }

    /**
     * Sets value of progress bar.
     *
     * @private
     * @function setValue
     * @memberof Bolt.buic.progress
     *
     * @param {Object} bar - The progress bar to set.
     * @param {float} value - A float between 0 and 1.
     */
    function setValue(bar, value) {
        value = parseFloat(value);
        value = isNaN(value) ? 0 : Math.min(100, Math.max(0, Math.round(value * 100)));

        $(bar).find('.progress-bar')
            .attr('aria-valuenow', value)
            .css('width', value + '%');
    }

    // Apply mixin container
    bolt.buic.progress = progress;

})(Bolt || {}, jQuery);
