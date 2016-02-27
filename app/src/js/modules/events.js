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

    /**
     * Attach an event handler.
     *
     * @static
     * @function init
     * @memberof Bolt.events
     *
     * @param {string}   event   - Event type
     * @param {function} handler - Event handler
     */
    events.on = function (event, handler) {
        $(this).on(event, handler);
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {});
