/**
 * Events.
 *
 * @mixin
 * @namespace Bolt.events
 *
 * eventType has to be in the form of: "namespace.domain.event.status"
 *
 * Available events:
 *
 * + On saving content:
 *   - "bolt.content.save.start"         : Before saving content
 *   - "bolt.content.save.done"          : Content was saved successfully
 *   - "bolt.content.save.fail"          : Saving content failed
 *   - "bolt.content.save.always"        : After saving content (failed or succeeded)
 *
 * + On saving an edited file:
 *   - "bolt.file.save.start"            : Before saving file
 *   - "bolt.file.save.done"             : File was saved successfully
 *   - "bolt.file.save.fail"             : Saving file failed
 *   - "bolt.file.save.always"           : After saving file (failed or succeeded)
 *
 * + Loading GoogleMaps API:
 *   - "bolt.googlemapsapi.load.start"   : Request loading API loading
 *   - "bolt.googlemapsapi.load.done"    : API loaded successfully
 *   - "bolt.googlemapsapi.load.fail"    : Loading failed
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
     var eventTypeRegex = /^([a-zA-Z0-9_-]+)(?:\.([a-zA-Z0-9_-]+))?(?:\.([a-zA-Z0-9_-]+))?(?:\.([a-zA-Z0-9_-]+))?$/;

    /**
     * Fires an event.
     *
     * @example
     *      Bolt.events.fire('myext.sampledata.save.start');
     *
     *
     * @static
     * @function fire
     * @memberof Bolt.events
     *
     * @param {string} eventType   - Event type (all four levels have to be specified)
     * @param {Object} [parameter] - Additional parameters to pass along to the event handler
     */
    events.fire = function (eventType, parameter) {
        var level = eventTypeRegex.exec(eventType);

        // If it's a valid event name and has four levels.
        if (level && typeof level[4] !== 'undefined') {
            while (eventType) {
                if (callbacks[eventType]) {
                    callbacks[eventType].fire(
                        {
                            namespace: level[1],
                            domain: level[2],
                            event: level[3],
                            status: level[4]
                        },
                        parameter
                    );
                }
                // Remove last level.
                eventType = eventType.replace(/\.?[a-zA-Z0-9_-]+$/, '');
            }
        }
    };

    /**
     * Attach an event handler.
     *
     * @example
     *      Bolt.events.on('myext.sampledata.save.done', function () {
     *          ...;
     *      });
     *
     * @example
     *      Bolt.events.on('myext.sampledata.save.done', function (event, data) {
     *          console.log(data.foobar);
     *      });
     *
     * @example
     *      Bolt.events.on('myext.sampledata.save', function (event) {
     *          if (event.status === 'start') {
     *              ...
     *          }
     *          if (event.status === 'done') {
     *              ...
     *          }
     *      });
     *
     * @static
     * @function on
     * @memberof Bolt.events
     *
     * @param {string}              eventType - Event type (less than four levels are allowed)
     * @param {function|function[]} handler   - Event handler
     */
    events.on = function (eventType, handler) {
        if (eventTypeRegex.exec(eventType)) {
            if (!callbacks[eventType]) {
                callbacks[eventType] = $.Callbacks('unique');
            }
            callbacks[eventType].add(handler);
        }
    };

    /**
     * Remove an event handler.
     *
     * @example
     *      var handler = function () {
     *          ...;
     *      };
     *      Bolt.events.on('myext.sampledata.save.done', handler);
     *      ...;
     *      Bolt.events.off('myext.sampledata.save.done', handler);
     *
     * @static
     * @function off
     * @memberof Bolt.events
     *
     * @param {string}              eventType - Event type (less than four levels are allowed)
     * @param {function|function[]} handler   - Event handler
     */
    events.off = function (eventType, handler) {
        if (eventTypeRegex.exec(eventType) && callbacks[eventType]) {
            callbacks[eventType].remove(handler);
        }
    };

    // Apply mixin container
    bolt.events = events;

})(Bolt || {}, jQuery);
