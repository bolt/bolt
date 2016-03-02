/**
 * Events.
 *
 * @mixin
 * @namespace Bolt.events
 *
 * eventType has to be "namespace>domain>event>status"
 *
 * Available events:
 *
 * + On saving content:
 *   - "bolt>content>save>start"         : Before saving content
 *   - "bolt>content>save>done"          : Content was saved successfully
 *   - "bolt>content>save>fail"          : Saving content failed
 *   - "bolt>content>save>always"        : After saving content (failed or succeeded)
 *
 * + On saving an edited file:
 *   - "bolt>file>save>start"            : Before saving file
 *   - "bolt>file>save>done"             : File was saved successfully
 *   - "bolt>file>save>fail"             : Saving file failed
 *   - "bolt>file>save>always"           : After saving file (failed or succeeded)
 *
 * + Loading GoogleMaps API:
 *   - "bolt>googlemapsapi>load>start"   : Request loading API loading
 *   - "bolt>googlemapsapi>load>done"    : API loaded successfully
 *   - "bolt>googlemapsapi>load>fail"    : Loading failed
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
     * Attach an one time event handler.
     *
     * @static
     * @function one
     * @memberof Bolt.events
     *
     * @param {string}   eventType - Event type
     * @param {function} handler   - Event handler
     */
    events.one = function (eventType, handler) {
        broker.one(eventType, handler);
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
