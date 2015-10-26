/**
 * Handling of image fields.
 *
 * @mixin
 * @namespace Bolt.fields.image
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
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
        bolt.uploads.bindField(fieldset, fconf);
    };

    /**
     * Updates the preview image.
     *
     * @static
     * @function updatePreview
     * @memberof Bolt.fields.image
     *
     * @param {string} key
     * @param {string} path
     */
    image.updatePreview = function (key, path) {
        var preview = $('#field-' + key).closest('fieldset').find('img'),
            width = preview.attr('width'),
            height = preview.attr('height'),
            url;

        if (path) {
            url = bolt.conf('paths.root') +'thumbs/' + width + 'x' + height + 'c/' + encodeURI(path);
        } else {
            url = bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';
        }

        $(preview).attr('src', url);
    };

    // Apply mixin container
    bolt.fields.image = image;

})(Bolt || {}, jQuery);
