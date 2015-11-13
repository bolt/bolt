/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Image field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldImage
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldImage', /** @lends jQuery.widget.bolt.fieldImage.prototype */ {
        /**
         * The constructor of the image field widget.
         *
         * @private
         */
        _create: function () {
            var fieldset = this.element,
                input = fieldset.find('input.path'),
                preview = fieldset.find('img'),
                width = preview.attr('width'),
                height = preview.attr('height');

            bolt.uploads.bindField($(fieldset));

            // Update the preview image on change.
            input.on('change', function () {
                var path = input.val(),
                    url = bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';

                if (path) {
                    url = bolt.conf('paths.root') +'thumbs/' + width + 'x' + height + 'c/' + encodeURI(path);
                }

                preview.attr('src', url);
            });
        }
    });
})(jQuery, Bolt);
