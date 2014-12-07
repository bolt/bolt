/**
 * Model, Collection and View for Imagelist.
 */

var ImageModel = Backbone.Model.extend({

    defaults: {
        id: null,
        filename: null,
        title: "Untitled image",
        order: 1
    },

    initialize: function () {
    }

});

var Imagelist = Backbone.Collection.extend({

    model: ImageModel,

    comparator: function (image) {
        return image.get('order');
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

var ImagelistHolder = Backbone.View.extend({

    initialize: function (id) {
        this.list = new Imagelist();
        var prelist = $('#' + this.id).val();
        if (prelist !== "") {
            prelist = $.parseJSON($('#' + this.id).val());
            _.each(prelist, function (item) {
                var image = new ImageModel({
                    filename: item.filename,
                    title: item.title,
                    id: this.list.length
                });
                this.list.add(image);
            }, this);
        }
        this.render();
        this.bindEvents();
    },

    render: function () {
        this.list.sort();

        var list = $('#imagelist-' + this.id + ' .list'),
            data = list.data('list'),
            index = 0;

        list.html('');
        _.each(this.list.models, function (image) {
            image.set('id', index++);
            list.append(data.item.
                replace('<ID>', image.get('id')).
                replace('<VAL>', _.escape(image.get('title'))).
                replace('<PATH>', bolt.paths.bolt).
                replace('<FNAME>', image.get('filename'))
            );
        });
        if (this.list.models.length === 0) {
            list.append(data.empty);
        }
        this.serialize();
    },

    add: function (filename, title) {
        var image = new ImageModel({
            filename: filename,
            title: title,
            id: this.list.length
        });

        this.list.add(image);
        this.render();
    },

    remove: function (id) {
        _.each(this.list.models, function (item) {
            if (item.get('id') === id) {
                this.list.remove(item);
            }
        }, this);
        this.render();
    },

    serialize: function () {
        var ser = JSON.stringify(this.list);
        $('#' + this.id).val(ser);
    },

    doneSort: function () {
        var list = this.list; // jQuery's .each overwrites 'this' scope, set it here.
        $('#imagelist-' + this.id + ' .list div').each(function (index) {
            var id = $(this).data('id'),
                title = $(this).find('input').val();

            list.setOrder(id, index, title);
        });
        this.render();
    },

    bindEvents: function () {
        var $this = this,
            contentkey = this.id,
            $holder = $('#imagelist-' + this.id);

        $holder.find("div.list").sortable({
            stop: function () {
                $this.doneSort();
            },
            delay: 100,
            distance: 5
        });

        $('#fileupload-' + contentkey)
            .fileupload({
                dataType: 'json',
                dropZone: $holder,
                done: function (e, data) {
                    $.each(data.result, function (index, file) {
                        var filename = decodeURI(file.url).replace("files/", "");
                        $this.add(filename, filename);
                    });
                }
            })
            .bind('fileuploadsubmit', function (e, data) {
                var fileTypes = $('#fileupload-' + contentkey).attr('accept'),
                    pattern,
                    ldata = $('#imagelist-' + contentkey + ' div.list').data('list');

                if (typeof fileTypes !== 'undefined') {
                    pattern = new RegExp("\\.(" + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ")$", "i");
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            alert(ldata.message.wrongtype);
                            e.preventDefault();

                            return false;
                        }
                    });
                }
            });

        $holder.find('div.list').on('click', 'a', function (e) {
            var ldata = $(this).closest('div.list').data('list');

            e.preventDefault();
            if (confirm(ldata.message.remove)) {
                $this.remove($(this).parent().data('id'));
            }
        });

        $holder.find("div.list").on('blur', 'input', function () {
            $this.doneSort();
        });

        // In the modal dialog, to navigate folders.
        $('#selectImageModal-' + contentkey).on('click', '.folder', function (e) {
            e.preventDefault();
            $('#selectImageModal-' + contentkey + ' .modal-content').load($(this).data('action'));
        });

        // In the modal dialog, to select a file.
        $('#selectImageModal-' + contentkey).on('click', '.file', function (e) {
            e.preventDefault();
            var filename = $(this).data('action');
            $this.add(filename, filename);
        });
    }

});
