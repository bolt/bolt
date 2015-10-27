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
        //Adds a new progress bar to the progress bar container.
        $(buic).on('buic:progress-add', function (event, label, value) {
            add(buic, label || '', value || 0);
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
     * @param {integer} value - An integer between 0 and 100.
     */
    function add(progress, label, value) {
        var bar = $(bolt.data('buic.progress.bar'));

        $(bar)
            .attr('data-label', label)
            .find('.progress-bar')
            .text(label);

        setValue(bar, value);

        $(progress).append(bar);
    }

    /**
     * Sets value of progress bar.
     *
     * @private
     * @function setValue
     * @memberof Bolt.buic.progress
     *
     * @param {Object} bar - The progress bar to set.
     * @param {integer} value - An integer between 0 and 100.
     */
    function setValue(bar, value) {
        $(bar).find('.progress-bar')
            .attr('aria-valuenow', value)
            .css('width', value + '%');
    }

    // Apply mixin container
    bolt.buic.progress = progress;

})(Bolt || {}, jQuery);
