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
     *
     * @private
     * @type {Object}
     */
    var events = {};

    /*
     * Event broker object.
     *
     * @private
     * @type {Object}
     */
    var broker = $({});

    /**
     * Fires an event.
     *
     * @static
     * @function fire
     * @memberof Bolt.events
     *
     * @param {string} eventType   - Event type
     * @param {Object} [parameter] - Additional parameters to pass along to the event handler
     */
    events.fire = function (eventType, parameter) {
        broker.triggerHandler(eventType, parameter);
    };

    /**
     * Attach an event handler.
     *
     * @static
     * @function on
     * @memberof Bolt.events
     *
     * @param {string}   eventType - Event type
     * @param {function} handler   - Event handler
     */
    events.on = function (eventType, handler) {
        broker.on(eventType, handler);
    };

    /**
     * Remove an event handler.
     *
     * @static
     * @function off
     * @memberof Bolt.events
     *
     * @param {string}   eventType - Event type
     * @param {function} handler   - Event handler
     */
    events.off = function (eventType, handler) {
        if (typeof eventType === 'string' && eventType !== '' && typeof handler === 'function') {
            broker.off(eventType, handler);
        }
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {});
