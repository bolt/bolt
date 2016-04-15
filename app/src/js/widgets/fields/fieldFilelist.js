/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Filelist field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldFilelist
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldFilelist', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldFilelist.prototype */ {
        /**
         * The constructor of the filelist field widget.
         *
         * @private
         * @listens jQuery.widget.bolt.buicBrowser#buicbrowserselected
         * @listens jQuery.widget.bolt.buicUpload#buicuploaduploaded
         */
        _create: function () {
            var self = this,
                fieldset = this.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldFilelist.prototype
             * @private
             *
             * @property {Object} data - List data holder
             * @property {Object} list - List container
             */
            this._ui = {
                data: fieldset.find('textarea'),
                list: fieldset.find('.list')
            };

            /**
             * Index of the last clicked item.
             *
             * @type {number}
             * @name _lastClickIndex
             * @memberOf jQuery.widget.bolt.fieldFilelist.prototype
             * @private
             */
            this._lastClickIndex = 0;

            // Mark this widget as type of "FileList", if not already set.
            self.options.isImage = this.options.isImage || false;

            // Make the list sortable.
            self._ui.list.sortable({
                // Set a helper element to be used for dragging display.
                helper: function (event, item) {
                    // We select the item dragged, as it isn't selected on a single item drag.
                    item.addClass('selected');

                    return $('<div/>');
                },
                // Triggered when sorting starts.
                start: function (event, ui) {
                    var elements = $('.selected', fieldset).not('.ui-sortable-placeholder'),
                        itemCount = elements.length,
                        placeholder = ui.placeholder,
                        outerHeight = placeholder.outerHeight(true),
                        innerHeight = placeholder.height(),
                        margin = parseInt(placeholder.css('margin-top')) + parseInt(placeholder.css('margin-bottom'));

                    elements.hide();
                    placeholder.height(innerHeight + (itemCount - 1) * outerHeight - margin);
                    ui.item.data('items', elements);
                },
                // Triggered when sorting stops, but when the placeholder/helper is still available.
                beforeStop: function (event, ui) {
                    ui.item.before(ui.item.data('items'));
                },
                // Triggered when sorting has stopped.
                stop: function () {
                    $('.selected', fieldset).show();
                    self._serialize();
                },
                // Set on which axis items items can be dragged.
                axis: 'y',
                // Time in milliseconds to define when the sorting should start.
                delay: 100,
                // Tolerance, in pixels, for when sorting should start.
                distance: 5
            });

            // Bind list events.
            self._on(self._ui.list, {
                'click.item':   self._onSelect,
                'click.remove': self._onRemove,
                'change input': self._serialize
            });

            // For some reason "keyup" does not work with _on(), so for now…
            $('input.title', self._ui.list).on('keyup', self._updateTitle);

            // Binds event handlers.
            self._on({
                'click.select-from-stack a': self._onSelectFromStack,
                'buicbrowserselected':       self._onAddPath,
                'buicuploaduploaded':        self._onAddPath
            });

            // Bind upload.
            fieldset.buicUpload();
        },

        /**
         * Adds a file path to the list.
         *
         * @private
         *
         * @param {Object}                                             event - The event
         * @param {jQuery.widget.bolt.buicBrowser#buicbrowserselected|
         *         jQuery.widget.bolt.buicUpload#buicuploaduploaded|
         *         Object}                                             data  - Data containing the path
         */
        _onAddPath: function (event, data) {
            // Remove empty list message, if there.
            $('>p', this._ui.list).remove();

            // Append to list.
            this._ui.list.append(
                $(Bolt.data(
                    this.options.isImage ? 'field.imagelist.template.item' : 'field.filelist.template.item',
                    {
                        '%TITLE_A%':    data.path.replace(/\.[a-z0-9]+$/, ''),
                        '%FILENAME_E%': $('<div>').text(data.path).html(), // Escaped
                        '%FILENAME_A%': data.path,
                        '%EXT_E%':      data.path.replace(/^.+?\.([a-z0-9]+)$/, '$1').toUpperCase()
                    }
                ))
            );

            this._serialize();
        },

        /**
         * Handles on the list item remove button clicks.
         *
         * @private
         *
         * @param {Object} event - The event
         */
        _onRemove: function (event) {
            var item = $(event.target).closest('.item'),
                items = item.hasClass('selected') ? $('.selected', this._ui.list) : item,
                msgOne = this.options.isImage ?
                    'field.imagelist.message.remove' : 'field.filelist.message.remove',
                msgMlt = this.options.isImage ?
                    'field.imagelist.message.removeMulti' : 'field.filelist.message.removeMulti';

            items.addClass('zombie');
            if (confirm(bolt.data(items.length > 1 ? msgMlt : msgOne))) {
                event.target.closest('.item').remove();
                this._serialize();
            } else {
                items.removeClass('zombie');
            }

            event.preventDefault();
            event.stopPropagation();
        },

        /**
         * Handles clicks on items.
         *
         * @private
         *
         * @param {Object} event - The event
         */
        _onSelect: function (event) {
            var item = $(event.target);

            if (item.hasClass('item')) {
                if (event.shiftKey) {
                    var begin = Math.min(this._lastClickIndex, item.index()),
                        end = Math.max(this._lastClickIndex, item.index());

                    // Select all items in range.
                    this._ui.list.children().each(function (idx, listitem) {
                        $(listitem).toggleClass('selected', idx >= begin && idx <= end);
                    });
                } else if (event.ctrlKey || event.metaKey) {
                    item.toggleClass('selected');
                    // Remember last clicked item.
                    this._lastClickIndex = item.index();
                } else {
                    var otherSelectedItems = this._ui.list.children('.selected').not(item);

                    // Unselect all other selected items.
                    otherSelectedItems.removeClass('selected');
                    // Select if others were selected, otherwise toogle.
                    item.toggleClass('selected', otherSelectedItems.length > 0 ? true : null);
                    // Remember last clicked item.
                    this._lastClickIndex = item.index();
                }
            }
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

            this._onAddPath(event, {path: link.data('path')});
            event.preventDefault();
        },

        /**
         * Serialize list data on change.
         */
        _serialize: function () {
            var template = this.options.isImage ? 'field.imagelist.template.empty' : 'field.filelist.template.empty',
                data = [];

            $('.item', this._ui.list).each(function () {
                data.push({
                    filename: $('input.filename', this).val(),
                    title: $('input.title', this).val()
                });
            });
            this._ui.data.val(JSON.stringify(data));

            // Display empty list message.
            if (data.length === 0) {
                this._ui.list.html(bolt.data(template));
            }
        },

        /**
         * Mirror changes on title into title attribute.
         *
         * @param {Object} event - The event
         */
        _updateTitle: function (event) {
            var item = $(event.target).closest('.item');

            $('a', item).attr('title', $('.title', item).val());
        }
    });
})(jQuery, Bolt);
