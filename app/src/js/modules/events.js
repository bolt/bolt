/**
 * Events.
 *
 * @mixin
 * @namespace Bolt.events
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /*
     * Bolt.events mixin container.
     */
    var events = {};

    /**
     * Fires an event.
     *
     * @static
     * @function init
     * @memberof Bolt.events
     *
     * @param {string} event - Event type
     */
    events.fire = function (event) {
        $(this).trigger(event);
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {});
