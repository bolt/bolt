/**
 * View for Filelist.
 */

var FilelistHolder = Backbone.View.extend({

    initialize: function (options) {
        this.fieldset = $(options.fieldset);
        this.list = $('div.list', options.fieldset);
        this.data = $('textarea', options.fieldset);

        if (options.type === 'imagelist') {
            this.datRemove = 'field.imagelist.message.remove';
            this.datRemoveMulti = 'field.imagelist.message.removeMulti';
            this.tmplEmpty = 'field.imagelist.template.empty';
            this.tmplItem = 'field.imagelist.template.item';
        } else {
            this.datRemove = 'field.filelist.message.remove';
            this.datRemoveMulti = 'field.filelist.message.removeMulti';
            this.tmplEmpty = 'field.filelist.template.empty';
            this.tmplItem = 'field.filelist.template.item';
        }

        this.bindEvents();
    },

    add: function (filename, title) {
        // Remove empty list message, if there.
        $('>p', this.list).remove();

        // Append to list.
        this.list.append(
            $(Bolt.data(
                this.tmplItem,
                {
                    '%VAL%':   $('<div>').text(title).html(), // Escaped
                    '%PATH%':  Bolt.conf('paths.bolt'),
                    '%FNAME%': filename
                }
            ))
        );

        this.serialize();
    },

    serialize: function () {
        var data = [];

        $('.list-item', this.list).each(function () {
            var input = $(this).find('input'),
                title = input.val(),
                filename = $(this).find('input').data('filename');

            data.push({
                filename: filename,
                title: title
            });
        });
        this.data.val(JSON.stringify(data));

        // Display empty list message.
        if (data.length === 0) {
            this.list.html(Bolt.data(this.tmplEmpty));
        }
    },

    bindEvents: function () {
        var thislist = this,
            fieldset = this.fieldset,
            lastClick = null;

        this.list
            .sortable({
                helper: function (e, item) {
                    if (!item.hasClass('selected')) {
                        item.toggleClass('selected');
                    }

                    return $('<div></div>');
                },
                start: function (e, ui) {
                    var elements = fieldset.find('.selected').not('.ui-sortable-placeholder'),
                        len = elements.length,
                        currentOuterHeight = ui.placeholder.outerHeight(true),
                        currentInnerHeight = ui.placeholder.height(),
                        margin = parseInt(ui.placeholder.css('margin-top')) +
                            parseInt(ui.placeholder.css('margin-bottom'));

                    elements.css('display', 'none');
                    ui.placeholder.height(currentInnerHeight + len * currentOuterHeight - currentOuterHeight - margin);
                    ui.item.data('items', elements);
                },
                beforeStop: function (e, ui) {
                    ui.item.before(ui.item.data('items'));
                },
                stop: function () {
                    fieldset.find('.ui-state-active').css('display', '');
                    thislist.serialize();
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
                        null : $(this);
                }
            })
            .on('click', '.remove-button', function (evt) {
                evt.preventDefault();

                if (confirm(Bolt.data(thislist.datRemove))) {
                    $(this).closest('.list-item').remove();
                    thislist.serialize();
                }
            })
            .on('change', 'input', function () {
                thislist.serialize();
            });

        fieldset.find('.remove-selected-button').on('click', function () {
            if (confirm(Bolt.data(thislist.datRemoveMulti))) {
                fieldset.find('.selected').closest('.list-item').remove();
                thislist.serialize();
            }
        });
    },

    uploadDone: function (result) {
        var that = this;

        $.each(result, function (idx, file) {
            that.add(file.name, file.name);
        });
    }
});
