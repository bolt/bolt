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
 * @param {Object} $ - jQuery
 */
(function (bolt, $) {
    'use strict';

    /*
     * Bolt.events mixin container.
     *
     * @private
     * @type {Object}
     */
    var events = {};

    /*
     * Event callbacks object.
     *
     * @private
     * @type {Object}
     */
    var callbacks = {};

    /*
     * Regular expression to check valid event types and to split them.
     *
     * @private
     * @type {Object}
     */
     var eventTypeRegex = /^(\w+)(?:>(\w+))?(?:>(\w+))?(?:>(\w+))?$/;

    /**
     * Bult an event object from an event type string.
     *
     * @private
     * @static
     * @function getEvent
     * @memberof Bolt.files
     *
     * @param {string} eventType   - Event type
     * @returns {Object|null} event - Parsed event type.
     */
    function getEvent(eventType) {
        var res = eventTypeRegex.exec(eventType),
            event = null;

        if (res) {
            event = {
                namespace: res[1],
                domain: res[2],
                event: res[3],
                status: res[4]
            };
        }

        return event;
    }

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
        var event = getEvent(eventType);

        if (event && typeof event.status !== 'undefined') {
            while (eventType) {
                if (callbacks[eventType]) {
                    callbacks[eventType].fireWith(event, [parameter]);
                }
                eventType = eventType.replace(/>?\w+$/, '');
            }
        }
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
        var event = getEvent(eventType);

        if (event) {
            if (!callbacks[eventType]) {
                callbacks[eventType] = $.Callbacks();
            }
            callbacks[eventType].add(handler);
        }
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
        var event = getEvent(eventType);

        if (event && typeof handler === 'function') {
            if (callbacks[eventType]) {
                callbacks[eventType].remove(handler);
            }
        }
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {}, jQuery);
