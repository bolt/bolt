/**
 * Handling of image fields.
 *
 * @mixin
 * @namespace Bolt.fields.image
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.image mixin container.
     *
     * @private
     * @type {Object}
     */
    var image = {};

    /**
     * Bind image field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.image
     *
     * @param {Object} fieldset
     * @param fconf
     */
    image.init = function (fieldset, fconf) {
        console.log('Binding Image Field', $);
        bolt.uploads.bind(fieldset, fconf);
    };

    // Apply mixin container
    bolt.fields.image = image;

})(Bolt || {}, jQuery);
