/**
 * Events.
 *
 * @mixin
 * @namespace Bolt.events
 *
 * eventType has to be "event.<bolt|extensionname>[.subnamespace(s)]"
 *
 * Available events:
 *
 * + On saving content:
 *   - "start.bolt.content.save"   : Before saving content
 *   - "done.bolt.content.save"    : Content was saved successfully
 *   - "fail.bolt.content.save"    : Saving content failed
 *   - "always.bolt.content.save"  : After saving content (failed or succeeded)
 *
 * + On saving an edited file:
 *   - "start.bolt.file.save"     : Before saving file
 *   - "done.bolt.file.save"      : File was saved successfully
 *   - "fail.bolt.file.save"      : Saving file failed
 *   - "always.bolt.file.save"    : After saving file (failed or succeeded)
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
