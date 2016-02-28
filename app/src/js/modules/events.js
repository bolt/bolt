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
    var broker = $();

    /**
     * Fires an event.
     *
     * @static
     * @function fire
     * @memberof Bolt.events
     *
     * @param {string} event       - Event type
     * @param {object} [parameter] - Additional parameters to pass along to the event handler
     */
    events.fire = function (event, parameter) {
        broker.trigger(event, parameter);
    };

    /**
     * Attach an event handler.
     *
     * @static
     * @function on
     * @memberof Bolt.events
     *
     * @param {string}   event   - Event type
     * @param {function} handler - Event handler
     */
    events.on = function (event, handler) {
        broker.on(event, handler);
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {});
