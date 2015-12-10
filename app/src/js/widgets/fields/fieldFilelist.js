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

            // Mark this widget as type of "FileList", if not already set.
            this.options.isImage = isImage;

            // Make the list sortable.
            $('div.list', fieldset).sortable({
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
            var list = $('div.list', fieldset);

            this._on(list, {
                'click.list-item': function (event) {
                    var item = $(event.target);

                    if (item.hasClass('list-item')) {
                        if (event.shiftKey) {
                            var begin = Math.min(lastClickIndex, item.index()),
                                end = Math.max(lastClickIndex, item.index());

                            // Select all items in range.
                            list.children().each(function (idx, listitem) {
                                $(listitem).toggleClass('selected', idx >= begin && idx <= end);
                            });
                        } else if (event.ctrlKey || event.metaKey) {
                            item.toggleClass('selected');
                            // Remember last clicked item.
                            lastClickIndex = item.index();
                        } else {
                            var otherSelectedItems = list.children('.selected').not(item);

                            // Unselect all other selected items.
                            otherSelectedItems.removeClass('selected');
                            // Select if others were selected, otherwise toogle.
                            item.toggleClass('selected', otherSelectedItems.length > 0 ? true : null);
                            // Remember last clicked item.
                            lastClickIndex = item.index();
                        }
                    }
                },
                'click.remove-button': function (event) {
                    var msg = isImage ? 'field.imagelist.message.remove' : 'field.filelist.message.remove';

                    event.preventDefault();
                    event.stopPropagation();

                    if (confirm(bolt.data(msg))) {
                        event.target.closest('.list-item').remove();
                        self._serialize();
                    }
                },
                'change input': function () {
                    self._serialize();
                }
            });

                /*$('div.list', fieldset)*/
                /*.on('click', '.list-item', function (event) {
                    if ($(event.target).hasClass('list-item')) {
                        if (event.shiftKey) {
                            if (lastClick) {
                                var currentIndex = $(this).index(),
                                    lastIndex = lastClick.index();

                                if (lastIndex > currentIndex) {
                                    $(this).nextUntil(lastClick).add(this).add(lastClick).addClass('selected');
                                } else if (lastIndex < currentIndex) {
                                    $(this).prevUntil(lastClick).add(this).add(lastClick).addClass('selected');
                                } else {
                                    $(this).toggleClass('selected');
                                }
                            }
                        } else if (event.ctrlKey || event.metaKey) {
                            $(this).toggleClass('selected');
                        } else {
                            $('.list-item', fieldset).not($(this)).removeClass('selected');
                            $(this).toggleClass('selected');
                        }

                        lastClick = event.shiftKey || event.ctrlKey || event.metaKey || $(this).hasClass('selected') ?
                            $(this) : null;
                    }
                })*/
                /*.on('click', '.remove-button', function (event) {
                    var msg = isImage ? 'field.imagelist.message.remove' : 'field.filelist.message.remove';

                    event.preventDefault();

                    if (confirm(bolt.data(msg))) {
                        $(this).closest('.list-item').remove();
                        self._serialize();
                    }
                })*/
                /*.on('change', 'input', function () {
                    self._serialize();
                });*/

            $('.remove-selected-button', fieldset).on('click', function () {
                var msg = isImage ? 'field.imagelist.message.removeMulti' : 'field.filelist.message.removeMulti';

                if (confirm(bolt.data(msg))) {
                    $('.selected', fieldset).closest('.list-item').remove();
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
            var listField = $('div.list', this.element),
                dataField = $('textarea', this.element),
                template = this.options.isImage ? 'field.imagelist.template.empty' : 'field.filelist.template.empty',
                data = [];

            $('.list-item', listField).each(function () {
                data.push({
                    filename: $(this).find('input.filename').val(),
                    title: $(this).find('input.title').val()
                });
            });
            dataField.val(JSON.stringify(data));

            // Display empty list message.
            if (data.length === 0) {
                listField.html(bolt.data(template));
            }
        }
    });
})(jQuery, Bolt);
