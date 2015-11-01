/**
 * Handling of imagelist fields.
 *
 * @mixin
 * @namespace Bolt.fields.imagelist
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /**
     * Bolt.fields.imagelist mixin container.
     *
     * @private
     * @type {Object}
     */
    var imagelist = {};

    /**
     * Bind image field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.image
     *
     * @param {Object} fieldset
     */
    imagelist.init = function (fieldset) {
        bolt.uploads.bindList(
            fieldset,
            {
                removeSingle: bolt.data('field.imagelist.message.remove'),
                removeMulti: bolt.data('field.imagelist.message.removeMulti')
            }
        );
    };

    // Apply mixin container
    bolt.fields.imagelist = imagelist;

})(Bolt || {}, jQuery);
