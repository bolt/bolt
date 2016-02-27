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
     * @param {string} event       - Event type
     * @param {object} [parameter] - Additional parameters to pass along to the event handler
     */
    events.fire = function (event, parameter) {
        $(this).trigger(event, parameter);
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
