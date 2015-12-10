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
         * @listens jQuery.widget.bolt.buicBrowser#buicbrowserselected
         * @listens Bolt.uploads#uploaduploaded
         */
        _create: function () {
            var self = this,
                fieldset = this.element;

            bolt.uploads.bindUpload(fieldset, false);
            bolt.uploads.bindSelectFromStack(fieldset);

            // Initialize the autocomplete popup.
            var accept = ($('input[accept]', fieldset).prop('accept') || '').replace(/\./g, ''),
                input = $('input.path', fieldset);

            input.autocomplete({
                source: bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
                minLength: 2,
                close: function () {
                    $(input).trigger('change');
                }
            });

            // Listen to external events.
            self._on({
                'buicbrowserselected': self._setPath,
                'uploaduploaded':      self._setPath
            });
        },

        /**
         * Sets the path to file.
         *
         * @private
         *
         * @param {Object}                                             event - The event
         * @param {jQuery.widget.bolt.buicBrowser#buicbrowserselected|
         *         Bolt.uploads#uploaduploaded}                        data  - Data containing the path
         */
        _setPath: function (event, data) {
            $('input.path', this.element)
                .val(data.path)
                .trigger('change');

            bolt.stack.addToStack(data.path);
        }
    });
})(jQuery, Bolt);
