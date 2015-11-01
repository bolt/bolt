/**
 * Handling of filelist fields.
 *
 * @mixin
 * @namespace Bolt.fields.filelist
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /**
     * Bolt.fields.filelist mixin container.
     *
     * @private
     * @type {Object}
     */
    var filelist = {};

    /**
     * Bind filelist field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.filelist
     *
     * @param {Object} fieldset
     */
    filelist.init = function (fieldset) {
        bolt.uploads.bindList(
            fieldset,
            {
                removeSingle: bolt.data('field.filelist.message.remove'),
                removeMulti: bolt.data('field.filelist.message.removeMulti')
            }
        );
    };

    // Apply mixin container
    bolt.fields.filelist = filelist;

})(Bolt || {}, jQuery);
