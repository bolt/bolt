/**
 * Model, Collection and View for Filelist.
 */

var FileModel = Backbone.Model.extend({

    defaults: {
        id: null,
        filename: null,
        title: "Untitled file",
        order: 1
    },

    initialize: function () {
    }

});

var FilelistModel = Backbone.Model.extend({

    defaults: {
        id: null,
        filename: null,
        title: "Untitled file",
        order: 1
    },

    initialize: function () {
    }

});

var Filelist = Backbone.Collection.extend({

    model: FilelistModel,

    comparator: function (file) {
        return file.get('order');
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

    initialize: function (id) {
        this.list = new Filelist();
        var prelist = $('#' + this.id).val();
        if (prelist !== "") {
            var prelist = $.parseJSON($('#' + this.id).val());
            _.each(prelist, function (item) {
                var file = new FilelistModel({
                    filename: item.filename,
                    title: item.title,
                    id: this.list.length
                });
                this.list.add(file);
            }, this);
        }
        this.render();
        this.bindEvents();
    },

    render: function () {
        this.list.sort();

        var $list = $('#filelist-' + this.id + ' .list');
        $list.html('');
        _.each(this.list.models, function (file) {
            var fileName = file.get('filename'),
                html = "<div data-id='" + file.get('id') + "' class='ui-state-default'>" +
                        "<span class='fileDescription'>" + fileName + "</span>" +
                        "<input type='text' value='" + _.escape(file.get('title')) + "'>" +
                        "<a href='#'><i class='fa fa-times'></i></a></div>";
            $list.append(html);
        });
        if (this.list.models.length === 0) {
            $list.append("<p>No files in the list, yet.</p>");
        }
        this.serialize();
    },

    add: function (filename, title) {
        var file = new FileModel({
            filename: filename,
            title: title,
            id: this.list.length
        });

        this.list.add(file);
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
        $('#filelist-' + this.id + ' .list div').each(function (index) {
            var id = $(this).data('id'),
                title = $(this).find('input').val();

            list.setOrder(id, index, title);
        });
        this.render();
    },

    bindEvents: function () {
        var $this = this,
            contentkey = this.id,
            $holder = $('#filelist-' + this.id);

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
                var fileTypes = $('#fileupload-' + contentkey).attr('accept');

                if (typeof fileTypes !== 'undefined') {
                    var pattern = new RegExp("\.(" + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ")$", "i");
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            var message = "Oops! There was an error uploading the file. Make sure that the file " +
                                "type is correct.\n\n(accept type was: " + fileTypes + ")";
                            alert(message);
                            e.preventDefault();
                            return false;
                        }
                    });
                }
            });

        $holder.find("div.list").on('click', 'a', function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this image?')) {
                var id = $(this).parent().data('id');
                $this.remove(id);
            }
        });

        $holder.find("div.list").on('blur', 'input', function () {
            $this.doneSort();
        });
    }

});
