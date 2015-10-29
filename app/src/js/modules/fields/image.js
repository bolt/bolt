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

        // Update the preview image on change.
        $('#field-' + fconf.key).on('change', function () {
            var preview = $(fieldset).find('img'),
                width = preview.attr('width'),
                height = preview.attr('height'),
                path = $('#field-' + fconf.key).val(),
                url;

            if (path) {
                url = bolt.conf('paths.root') +'thumbs/' + width + 'x' + height + 'c/' + encodeURI(path);
            } else {
                url = bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';
            }

            $(preview).attr('src', url);
        });
    };

    /**
     * Initializes a cloned image field.
     *
     * @static
     * @function initClone
     * @memberof Bolt.fields.image
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    image.initClone = function (fieldset, fconf) {
        //jshint unused:vars
    };

    // Apply mixin container
    bolt.fields.image = image;

})(Bolt || {}, jQuery);
