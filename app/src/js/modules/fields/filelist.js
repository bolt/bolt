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
     * @param fconf
     */
    filelist.init = function (fieldset, fconf) {
        bolt.uploads.bindFileList(fconf.key);
    };

    // Apply mixin container
    bolt.fields.filelist = filelist;

})(Bolt || {}, jQuery);
