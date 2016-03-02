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
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldFile', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldFile.prototype */ {
        /**
         * The constructor of the file field widget.
         *
         * @private
         * @listens jQuery.widget.bolt.buicBrowser#buicbrowserselected
         * @listens jQuery.widget.bolt.buicUpload#buicuploaduploaded
         */
        _create: function () {
            var self = this,
                fieldset = this.element;

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

            // Binds event handlers.
            self._on({
                'click.select-from-stack a': self._onSelectFromStack,
                'buicbrowserselected':       self._onSetPath,
                'buicuploaduploaded':        self._onSetPath
            });

            // Bind upload.
            fieldset.buicUpload();
        },

        /**
         * Sets the path to file.
         *
         * @private
         *
         * @param {Object} event - The event
         */
        _onSelectFromStack: function (event) {
            var link = $(event.target);

            // Close the dropdown.
            link.closest('.btn-group').removeClass('open');

            this._onSetPath(event, {path: link.data('path')});
            event.preventDefault();
        },

        /**
         * Sets the path to file.
         *
         * @private
         *
         * @param {Object}                                             event - The event
         * @param {jQuery.widget.bolt.buicBrowser#buicbrowserselected|
         *         jQuery.widget.bolt.buicUpload#buicuploaduploaded|
         *         Object}                                             data  - Data containing the path
         */
        _onSetPath: function (event, data) {
            $('input.path', this.element)
                .val(data.path)
                .trigger('change');

            if (event.type !== 'click') {
                bolt.stack.addToStack(data.path);
            }
        }
    });
})(jQuery, Bolt);
