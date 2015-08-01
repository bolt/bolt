/**
 * Handling of BUIC moments.
 *
 * @mixin
 * @namespace Bolt.buic.moment
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} momentjs - moment.js object.
 */
(function (bolt, $, momentjs) {
    'use strict';

    /**
     * Bolt.buic.moment mixin container.
     *
     * @private
     * @type {Object}
     */
    var moment = {};

    /**
     * Bind BUIC moments.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.moment
     *
     * @param {Object} buic
     */
    moment.init = function (buic) {
        // We initialize the loop only once.
        if (!intervalId) {
            intervalId = setInterval(
                function () {
                    $('.buic-moment').each(function () {
                        updateFromNow(this);
                    });
                },
                delay
            );
        }
        moment.set(buic);
    };

    /**
     * Sets new datetime.
     *
     * @static
     * @function set
     * @memberof Bolt.buic.moment
     *
     * @param {Object} buic time DOM element.
     * @param {string|undefined} datetime Datetime to set.
     */
    moment.set = function (buic, datetime) {
        if (typeof datetime === 'undefined') {
            datetime = $(buic).attr('datetime');
        } else {
            $(buic).attr('datetime', momentjs(datetime).format());
        }
        $(buic).attr('title', momentjs(datetime).format('YYYY-MM-DD HH:mm:ss ZZ'));

        updateFromNow(buic);
    };

    /**
     * Update time interval.
     *
     * @private
     * @const
     * @type {Object}
     */
    var delay = 15 * 1000; // 15 seconds

    /**
     * Interval id.
     *
     * @private
     * @type {integer}
     */
    var intervalId;

    /**
     * Bind BUIC moments.
     *
     * @private
     * @function updateFromNow
     * @memberof Bolt.buic.moment
     *
     * @param {Object} buic
     */
    function updateFromNow(buic) {
        $(buic).html(momentjs($(buic).attr('datetime')).fromNow());
    }

    // Apply mixin container
    bolt.buic.moment = moment;

})(Bolt || {}, jQuery, moment);
