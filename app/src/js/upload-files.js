/**
 * Model, Collection and View for Filelist.
 */

var FileModel = Backbone.Model.extend({

    defaults: {
        id: null,
        filename: null,
        title: "Untitled",
        order: 1,
        progress: 0,
        element: null
    },

    initialize: function () {
    }

});

var Filelist = Backbone.Collection.extend({

    model: FileModel,

    comparator: function (upload) {
        return upload.get('order');
    },

    setOrder: function (id, order, title) {
        _.each(this.models, function (item) {
            if (item.get('id') === id) {
                item.set('order', order);
                item.set('title', title);
            }
        });
    }

});

var FilelistHolder = Backbone.View.extend({

    initialize: function (options) {
        this.list = new Filelist();
        this.uploading = new Filelist();
        this.type = options.type;
        //
        this.fieldset = $(options.fieldset);
        this.data = $('textarea', options.fieldset);

        if (options.type === 'imagelist') {
            this.datWrongtype = 'field.imagelist.message.wrongtype';
            this.datRemove = 'field.imagelist.message.remove';
            this.datRemoveMulti = 'field.imagelist.message.removeMulti';
            this.tmplEmpty = 'field.imagelist.template.empty';
            this.tmplItem = 'field.imagelist.template.item';
        } else {
            this.datWrongtype = 'field.filelist.message.wrongtype';
            this.datRemove = 'field.filelist.message.remove';
            this.datRemoveMulti = 'field.filelist.message.removeMulti';
            this.tmplEmpty = 'field.filelist.template.empty';
            this.tmplItem = 'field.filelist.template.item';
        }

        var list = this.list;

        $.each($.parseJSON(this.data.val() || '[]'), function (idx, item) {
            list.add(
                new FileModel({
                    filename: item.filename,
                    title: item.title,
                    id: list.length
                })
            );
        });

        this.render();
        this.bindEvents();
    },

    render: function () {
        this.list.sort();

        var list = $('.list', this.fieldset),
            listtype = this.type,
            tmplItem = this.tmplItem;

        list.html('');
        _.each(this.list.models, function (file) {
            var replace = {
                    '%ID%':    file.get('id'),
                    '%VAL%':   _.escape(file.get('title')),
                    '%PATH%':  Bolt.conf('paths.bolt'),
                    '%FNAME%': file.get('filename')
                },
                element = $(Bolt.data(tmplItem, replace));

            if (listtype === 'imagelist') {
                element.find('.thumbnail-link').magnificPopup({type: 'image'});
            }
            list.append(element);
        });

        if (this.list.models.length === 0) {
            list.append(Bolt.data(this.tmplEmpty));
        }
        this.serialize();
    },

    add: function (filename, title) {
        this.list.add(
            new FileModel({
                filename: filename,
                title: title,
                id: this.list.length
            })
        );
        this.render();
    },

    remove: function (id, dontRender) {
        var done = false;
        _.each(this.list.models, function (item) {
            if (!done && item.get('id') === id) {
                this.list.remove(item);
                done = true;
            }
        }, this);

        if (!dontRender) {
            this.render();
        }
    },

    serialize: function () {
        this.data.val(JSON.stringify(this.list));
    },

    doneSort: function () {
        var list = this.list;

        $('.list div', this.fieldset).each(function (index) {
            var id = $(this).data('id'),
                title = $(this).find('input').val();

            list.setOrder(id, index, title);
        });
        this.render();
    },

    bindEvents: function () {
        var $this = this,
            thislist = this,
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
                thislist.doneSort();
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
                fieldset.find('.selected').each(function () {
                    thislist.remove($(this).data('id'), true);
                });
                thislist.render();
            }
        });

        fieldset.find('div.list').on('click', '.remove-button', function (e) {
            e.preventDefault();

            if (confirm(Bolt.data(thislist.datRemove))) {
                thislist.remove($(this).parent().data('id'));
            }
        });

        fieldset.find("div.list").on('blur', 'input', function () {
            thislist.doneSort();
        });
    },

    uploadDone: function (result) {
        var that = this;

        $.each(result, function (idx, file) {
            that.add(file.name, file.name);
        });
    },

    uploadSubmit: function (files) {
        var that = this;

        $.each(files, function (idx, file) {
            file.uploading = new FileModel({
                filename: file.name
            });
            that.uploading.add(file.uploading);
        });

       this.render();
    },

    uploadAlways: function (files) {
        var that = this;

        $.each(files, function (idx, file) {
            that.uploading.remove(file.uploading);
        });

        this.render();
    }
});
