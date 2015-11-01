/**
 * View for Filelist.
 */

var FilelistHolder = Backbone.View.extend({

    initialize: function (options) {
        this.list = $('div.list', options.fieldset);
        this.data = $('textarea', options.fieldset);

        // Bind events.
        var thislist = this,
            fieldset = $(options.fieldset),
            lastClick = null,
            isFile = options.type === 'filelist',
            message = {
                removeSingle: isFile ? 'field.filelist.message.remove' : 'field.imagelist.message.remove',
                removeMulti: isFile ? 'field.filelist.message.removeMulti' : 'field.imagelist.message.removeMulti'
            },
            template = {
                empty: isFile ? 'field.filelist.template.empty' : 'field.imagelist.template.empty',
                item: isFile ? 'field.filelist.template.item' : 'field.imagelist.template.item'
            };

        this.list
            .sortable({
                helper: function (evt, item) {
                    if (!item.hasClass('selected')) {
                        item.toggleClass('selected');
                    }

                    return $('<div></div>');
                },
                start: function (evt, ui) {
                    var elements = fieldset.find('.selected').not('.ui-sortable-placeholder'),
                        len = elements.length,
                        currentOuterHeight = ui.placeholder.outerHeight(true),
                        currentInnerHeight = ui.placeholder.height(),
                        margin = parseInt(ui.placeholder.css('margin-top')) +
                            parseInt(ui.placeholder.css('margin-bottom'));

                    elements.hide();
                    ui.placeholder.height(currentInnerHeight + len * currentOuterHeight - currentOuterHeight - margin);
                    ui.item.data('items', elements);
                },
                beforeStop: function (evt, ui) {
                    ui.item.before(ui.item.data('items'));
                },
                stop: function () {
                    fieldset.find('.selected').show();
                    thislist.serializeList();
                },
                delay: 100,
                distance: 5
            })
            .on('click', '.list-item', function (evt) {
                if ($(evt.target).hasClass('list-item')) {
                    if (evt.shiftKey) {
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
                    } else if (evt.ctrlKey || evt.metaKey) {
                        $(this).toggleClass('selected');
                    } else {
                        fieldset.find('.list-item').not($(this)).removeClass('selected');
                        $(this).toggleClass('selected');
                    }

                    lastClick = evt.shiftKey || evt.ctrlKey || evt.metaKey || $(this).hasClass('selected') ?
                        $(this) : null;
                }
            })
            .on('click', '.remove-button', function (evt) {
                evt.preventDefault();

                if (confirm(Bolt.data(message.removeSingle))) {
                    $(this).closest('.list-item').remove();
                    thislist.serializeList();
                }
            })
            .on('change', 'input', function () {
                thislist.serializeList();
            });

        fieldset.find('.remove-selected-button').on('click', function () {
            if (confirm(Bolt.data(message.removeMulti))) {
                fieldset.find('.selected').closest('.list-item').remove();
                thislist.serializeList();
            }
        });
    },

    addToList: function (filename, title) {
        var listField = this.list;

        // Remove empty list message, if there.
        $('>p', listField).remove();

        // Append to list.
        listField.append(
            $(Bolt.data(
                Bolt.data(template.item),
                {
                    '%TITLE_A%':    title,
                    '%FILENAME_E%': $('<div>').text(filename).html(), // Escaped
                    '%FILENAME_A%': filename
                }
            ))
        );

        this.serializeList();
    },

    serializeList: function () {
        var listField = this.list,
            dataField = this.data,
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
            listField.html(Bolt.data(template.empty));
        }
    },
});
