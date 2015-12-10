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
     */
    $.widget('bolt.fieldFilelist', /** @lends jQuery.widget.bolt.fieldFilelist.prototype */ {
        /**
         * The constructor of the filelist field widget.
         *
         * @private
         */
        _create: function () {
            var self = this,
                fieldset = this.element,
                isImage = this.options.isImage || false,
                lastClickIndex = 0;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldFilelist.prototype
             * @private
             *
             * @property {Object} data           - List data holder
             * @property {Object} list           - List container
             */
            this._ui = {
                data:           fieldset.find('textarea'),
                list:           fieldset.find('.list')
            };

            // Mark this widget as type of "FileList", if not already set.
            self.options.isImage = isImage;

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
            this._on(self._ui.list, {
                'click.item': function (event) {
                    var item = $(event.target);

                    if (item.hasClass('item')) {
                        if (event.shiftKey) {
                            var begin = Math.min(lastClickIndex, item.index()),
                                end = Math.max(lastClickIndex, item.index());

                            // Select all items in range.
                            self._ui.list.children().each(function (idx, listitem) {
                                $(listitem).toggleClass('selected', idx >= begin && idx <= end);
                            });
                        } else if (event.ctrlKey || event.metaKey) {
                            item.toggleClass('selected');
                            // Remember last clicked item.
                            lastClickIndex = item.index();
                        } else {
                            var otherSelectedItems = self._ui.list.children('.selected').not(item);

                            // Unselect all other selected items.
                            otherSelectedItems.removeClass('selected');
                            // Select if others were selected, otherwise toogle.
                            item.toggleClass('selected', otherSelectedItems.length > 0 ? true : null);
                            // Remember last clicked item.
                            lastClickIndex = item.index();
                        }
                    }
                },
                'click.remove': function (event) {
                    var item = $(event.target).closest('.item'),
                        items = item.hasClass('selected') ? $('.selected', self._ui.list) : item,
                        msgOne = isImage ? 'field.imagelist.message.remove' : 'field.filelist.message.remove',
                        msgMlt = isImage ? 'field.imagelist.message.removeMulti' : 'field.filelist.message.removeMulti';

                    items.addClass('zombie');
                    if (confirm(bolt.data(items.length > 1 ? msgMlt : msgOne))) {
                        event.target.closest('.item').remove();
                        self._serialize();
                    } else {
                        items.removeClass('zombie');
                    }

                    event.preventDefault();
                    event.stopPropagation();
                },
                'change input': function () {
                    self._serialize();
                }
            });

            // Bind events.
            bolt.uploads.bindUpload(fieldset, true);
            bolt.uploads.bindSelectFromStack(fieldset);
        },

        /**
         * Serialize list data on change.
         */
        _serialize: function () {
            var template = this.options.isImage ? 'field.imagelist.template.empty' : 'field.filelist.template.empty',
                data = [];

            $('.item', this._ui.list).each(function () {
                data.push({
                    filename: $(this).find('input.filename').val(),
                    title: $(this).find('input.title').val()
                });
            });
            this._ui.data.val(JSON.stringify(data));

            // Display empty list message.
            if (data.length === 0) {
                this._ui.list.html(bolt.data(template));
            }
        }
    });
})(jQuery, Bolt);
