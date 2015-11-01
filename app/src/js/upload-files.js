/**
 * View for Filelist.
 */

var FilelistHolder = Backbone.View.extend({

    initialize: function (options) {
        this.type = options.type;
        //
        this.fieldset = $(options.fieldset);
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

        var that = this,
            preset = $.parseJSON(this.data.val() || '[]');

        if (preset.length) {
            $.each(preset, function (idx, item) {
                that.add(item.filename, item.title);
            });
        } else {
            $('.list', this.fieldset).append(Bolt.data(this.tmplEmpty));
        }

        this.bindEvents();
    },

    add: function (filename, title) {
        // Remove empty list message, if there.
        $('.list>p', this.fieldset).remove();

        var replace = {
                '%VAL%':   _.escape(title),
                '%PATH%':  Bolt.conf('paths.bolt'),
                '%FNAME%': filename
            },
            element = $(Bolt.data(this.tmplItem, replace));

        $('.list', this.fieldset).append(element);

        this.serialize();
    },

    serialize: function () {
        var data = [];

        $('.list .list-item', this.fieldset).each(function () {
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
            $('.list', this.fieldset).html(Bolt.data(this.tmplEmpty));
        }
    },

    bindEvents: function () {
        var thislist = this,
            fieldset = this.fieldset;

        fieldset.find('div.list').sortable({
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
                    margin = parseInt(ui.placeholder.css('margin-top')) + parseInt(ui.placeholder.css('margin-bottom'));

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
        });

        var lastClick = null;
        fieldset.find('div.list').on('click', '.list-item', function (e) {
            if ($(e.target).hasClass('list-item')) {
                if (e.shiftKey) {
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
                } else if (e.ctrlKey || e.metaKey) {
                    $(this).toggleClass('selected');
                } else {
                    fieldset.find('.list-item').not($(this)).removeClass('selected');
                    $(this).toggleClass('selected');
                }

                if (!e.shiftKey && !e.ctrlKey && !e.metaKey && !$(this).hasClass('selected')) {
                    lastClick = null;
                } else {
                    lastClick = $(this);
                }
            }
        });

        fieldset.find('.remove-selected-button').on('click', function () {
            if (confirm(Bolt.data(thislist.datRemoveMulti))) {
                fieldset.find('.selected').closest('.list-item').remove();
                thislist.serialize();
            }
        });

        fieldset.find('div.list').on('click', '.remove-button', function (evt) {
            evt.preventDefault();

            if (confirm(Bolt.data(thislist.datRemove))) {
                $(this).closest('.list-item').remove();
                thislist.serialize();
            }
        });

        fieldset.find('div.list').on('change', 'input', function () {
            thislist.serialize();
        });
    },

    uploadDone: function (result) {
        var that = this;

        $.each(result, function (idx, file) {
            that.add(file.name, file.name);
        });

        this.render();
    }
});
