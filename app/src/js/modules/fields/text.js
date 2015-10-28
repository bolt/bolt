/**
 * Handling of text input fields.
 *
 * @mixin
 * @namespace Bolt.fields.text
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.text mixin container.
     *
     * @private
     * @type {Object}
     */
    var text = {};

    /**
     * Bind text field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.text
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    text.init = function (fieldset, fconf) {
        //jshint unused:vars
    };

    // Apply mixin container
    bolt.fields.text = text;

})(Bolt || {}, jQuery);
