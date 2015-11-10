/**
 * Auto-update the 'latest activity' widget.
 *
 * @mixin
 * @namespace Bolt.activity
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.activity mixin container.
     *
     * @private
     * @type {Object}
     */
    var activity = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.activity
     */
    activity.init = function () {
        if ($('#latestactivity').is('*')) {
            bolt.activity.update();
            setTimeout(
                function () {
                    bolt.activity.update();
                },
                interval
            );
        }
    };

    /**
     * Initializes the mixin.
     *
     * @static
     * @function update
     * @memberof Bolt.activity
     */
    activity.update = function () {
        $.get(
            bolt.conf('paths.async') + 'latestactivity',
            function (data) {
                var newActivity = $(data);

                newActivity.find('.buic-moment').buicMoment();
                $('#latestactivity').empty().append(newActivity);
            }
        );

        setTimeout(
            function () {
                bolt.activity.update();
            },
            interval
        );
    };

    /**
     * Update interval.
     *
     * @private
     * @constant {number} interval
     * @memberof Bolt.activity
     */
    var interval = 30 * 1000; // 30 seconds

    // Apply mixin container
    bolt.activity = activity;

})(Bolt || {}, jQuery);
