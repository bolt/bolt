/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * File field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldFile
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldFile', /** @lends jQuery.widget.bolt.fieldFile.prototype */ {
        /**
         * The constructor of the file field widget.
         *
         * @private
         */
        _create: function () {
            bolt.uploads.bindUpload(this.element, false);
            bolt.uploads.bindSelectFromStack(this.element);

            // Initialize the autocomplete popup.
            var accept = ($('input[accept]', this.element).prop('accept') || '').replace(/\./g, ''),
                input = $('input.path', this.element);

            input.autocomplete({
                source: bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
                minLength: 2,
                close: function () {
                    $(input).trigger('change');
                }
            });
        },

        /**
         * Sets the path to file.
         *
         * @param {string} path
         * @param {boolean} stackAdd
         */
        setPath: function (path, stackAdd) {
            $('input.path', this.element)
                .val(path)
                .trigger('change');

            if (stackAdd) {
                bolt.stack.addToStack(path);
            }
        }
    });
})(jQuery, Bolt);
