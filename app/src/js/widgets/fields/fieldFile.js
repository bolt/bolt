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
            var input = $('input.path', fieldset);

            // Until content field returns file objects, fake initially selected data here.
            input.data('selected', {
                path: input.val(),
            });

            input.autocomplete({
                source: fieldset.find('[data-autocomplete-url]').data('autocompleteUrl'),
                minLength: 2,
                focus: function (event, ui) {
                    self._onPreview(event, ui.item);
                    return false;
                },
                select: function (event, ui) {
                    self._onSelect(event, ui.item);
                    return false;
                },
                close: function () {
                    self._onClose();
                }
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
                return $('<li>')
                    .append(item.path)
                    .appendTo(ul);
            };
            input.on('change', function () {
                $(this).data('changeWithoutSelection', true);
            });
            input.on('blur', function () {
                if ($(this).data('changeWithoutSelection')) {
                    if ($(this).val() === '') {
                        self._onClear();
                    } else {
                        self._onClose();
                    }
                }
            });

            // Binds event handlers.
            self._on({
                'click.select-from-stack a': self._onSelectFromStack,
                'buicbrowserselected':       self._onSelect,
                'buicuploaduploaded':        self._onSelect,
            });

            // Bind upload.
            fieldset.buicUpload();
        },

        /**
         * Preview the item, but not select it.
         *
         * @param event
         * @param file
         * @private
         */
        _onPreview: function (event, file) {
            $('input.path', this.element).val(file.path);
        },

        /**
         * Sets the path to file.
         *
         * @private
         *
         * @param {Object} event - The event
         */
        _onSelectFromStack: function (event) {
            var link = $(event.target),
                fileItem = link.parent('[data-file]');

            // Close the dropdown.
            link.closest('.btn-group').removeClass('open');

            this._onSelect(event, fileItem.data('file'));
            event.preventDefault();
        },

        /**
         * Sets the path to file.
         *
         * @private
         *
         * @param {Object} event - The event
         * @param {Object} file  - Data containing the path
         */
        _onSelect: function (event, file) {
            $('input.path', this.element)
                .data('changeWithoutSelection', false)
                .data('selected', file)
                .val(file.path)
                .trigger('change');

            if (event.type !== 'click') {
                bolt.stack.addToStack(file.path);
            }
        },

        /**
         * Reset the selection to the last selected item.
         *
         * @private
         */
        _onClose: function () {
            var input = $('input.path', this.element);
            input.val(input.data('selected').path);
        },

        /**
         * Clear the selected item.
         *
         * @private
         */
        _onClear: function () {
            var input = $('input.path', this.element);
            input.val('');
            input.data('selected', {});
        },

    });
})(jQuery, Bolt);
