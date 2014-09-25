/*jslint browser: true, devel: true, debug: false, indent: 4, maxlen: 120, nomen: true, plusplus: true, sloppy: true */
/*global $ wysiwyg ckeditor_lang */

/*
 * Globals:
 * $:             jQuery
 *
 * wysiwyg:       inline _page.twig
 * ckeditor_lang: inline _page.twig
 */

// Don't break on browsers without console.log();
try {
    console.assert(1);
} catch(e) {
    console = {
        log: function () {},
        assert: function () {}
    };
}

/**
 * Helper to get all selected Items and return Array
 */
function getSelectedItems() {
    var aItems = [];
    $('.dashboardlisting input:checked').each(function (index) {
        if ($(this).parents('tr').attr('id')) {
            aItems.push($(this).parents('tr').attr('id').substr(5));
        }
    });
    console.log('getSelectedItems: ' + aItems);
    return aItems;
}

/**
 * Initialise CKeditor instances.
 */
CKEDITOR.editorConfig = function (config) {
    config.language = ckeditor_lang || 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = true;
    config.entities = false;
    config.extraPlugins = 'codemirror';
    config.toolbar = [
        { name: 'styles', items: [ 'Format' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
        { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] }
    ];

    if (wysiwyg.anchor) {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink', '-', 'Anchor' ] });
    } else {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink' ] });
    }

    if (wysiwyg.subsuper) {
        config.toolbar = config.toolbar.concat({ name: 'subsuper', items: [ 'Subscript', 'Superscript' ] });
    }
    if (wysiwyg.images) {
        config.toolbar = config.toolbar.concat({ name: 'image', items: [ 'Image' ] });
    }
    if (wysiwyg.embed) {
        config.extraPlugins += ',oembed,widget';
        config.oembed_maxWidth = '853';
        config.oembed_maxHeight = '480';
        config.toolbar = config.toolbar.concat({ name: 'embed', items: [ 'oembed' ] });
    }

    if (wysiwyg.tables) {
        config.toolbar = config.toolbar.concat({ name: 'table', items: [ 'Table' ] });
    }
    if (wysiwyg.align) {
        config.toolbar = config.toolbar.concat({ name: 'align', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] });
    }
    if (wysiwyg.fontcolor) {
        config.toolbar = config.toolbar.concat({ name: 'colors', items: [ 'TextColor', 'BGColor' ] });
    }

    config.toolbar = config.toolbar.concat({ name: 'tools', items: [ 'SpecialChar', '-', 'RemoveFormat', 'Maximize', '-', 'Source' ] });

    config.height = 250;
    config.autoGrow_onStartup = true;
    config.autoGrow_minHeight = 150;
    config.autoGrow_maxHeight = 400;
    config.autoGrow_bottomSpace = 24;
    config.removePlugins = 'elementspath';
    config.resize_dir = 'vertical';

    if (wysiwyg.filebrowser) {
        if (wysiwyg.filebrowser.browseUrl) {
            config.filebrowserBrowseUrl = wysiwyg.filebrowser.browseUrl;
        }
        if (wysiwyg.filebrowser.imageBrowseUrl) {
            config.filebrowserImageBrowseUrl = wysiwyg.filebrowser.imageBrowseUrl;
        }
        if (wysiwyg.filebrowser.uploadUrl) {
            config.filebrowserUploadUrl = wysiwyg.filebrowser.uploadUrl;
        }
        if (wysiwyg.filebrowser.imageUploadUrl) {
            config.filebrowserImageUploadUrl = wysiwyg.filebrowser.imageUploadUrl;
        }
    } else {
        config.filebrowserBrowseUrl = '';
        config.filebrowserImageBrowseUrl = '';
        config.filebrowserUploadUrl = '';
        config.filebrowserImageUploadUrl = '';
    }

    config.codemirror = {
        theme: 'default',
        lineNumbers: true,
        lineWrapping: true,
        matchBrackets: true,
        autoCloseTags: true,
        autoCloseBrackets: true,
        enableSearchTools: true,
        enableCodeFolding: true,
        enableCodeFormatting: true,
        autoFormatOnStart: true,
        autoFormatOnUncomment: true,
        highlightActiveLine: true,
        highlightMatches: true,
        showFormatButton: false,
        showCommentButton: false,
        showUncommentButton: false
    };

    /* Parse override settings from config.yml */
    for (var key in wysiwyg.ck) {
        if (wysiwyg.ck.hasOwnProperty(key)) {
             config[key] = wysiwyg.ck[key];
        }
    }
};


/**
 * Initialize 'moment' timestamps.
 */

var momentstimeout;

function updateMoments() {
    $('time.moment').each(function () {
        var stamp = moment($(this).attr('datetime'));
        $(this).html(stamp.fromNow());
    });
    clearTimeout(momentstimeout);
    momentstimeout = setTimeout(function () {
        updateMoments();
    }, 16 * 1000);
}

/**
 * Auto-update the 'latest activity' widget.
 */
function updateLatestActivity() {
    $.get(asyncpath + 'latestactivity', function (data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html($('#latesttemp').html());
    });

    setTimeout(function () {
        updateLatestActivity();
    }, 30 * 1000);
}

/**
 * Bind the file upload when editing content, so it works and stuff
 *
 * @param {string} key
 */
function bindFileUpload(key) {
    // Since jQuery File Upload's 'paramName' option seems to be ignored,
    // it requires the name of the upload input to be "images[]". Which clashes
    // with the non-fancy fallback, so we hackishly set it here. :-/
    $('#fileupload-' + key)
        .fileupload({
            dataType: 'json',
            dropZone: $('#dropzone-' + key),
            done: function (e, data) {
                $.each(data.result, function (index, file) {
                    if (file.error === undefined) {
                        var filename = decodeURI(file.url).replace("files/", "");
                        $('#field-' + key).val(filename);
                        $('#thumbnail-' + key).html("<img src='" + path + "../thumbs/120x120c/" + encodeURI(filename) + "' width='120' height='120'>");
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 1500);

                        // Add the uploaded file to our stack.
                        stack.addToStack(filename);

                    } else {
                        var message = "Oops! There was an error uploading the file. Make sure the file is not corrupt, and that the 'files/'-folder is writable."
                            + "\n\n(error was: "
                            + file.error + ")";

                        alert(message);
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 50);
                    }
                    $('#progress-' + key + ' div.bar').css('width', "100%");
                    $('#progress-' + key).removeClass('progress-striped active');
                });
            }
        })
        .bind('fileuploadprogress', function (e, data) {
            var progress = Math.round(100 * data._bitrateTimer.loaded / data.files[0].size);
            $('#progress-' + key).show().addClass('progress-striped active');
            $('#progress-' + key + ' div.bar').css('width', progress + "%");
        });
}

/**
 * Functions for working with the automagic URI/Slug generation.
 */

var makeuritimeout;

function makeUriAjax(text, contenttypeslug, id, slugfield, fulluri) {
    $.ajax({
        url: asyncpath + 'makeuri',
        type: 'GET',
        data: {
            title: text,
            contenttypeslug: contenttypeslug,
            id: id,
            fulluri: fulluri
        },
        success: function (uri) {
            $('#' + slugfield).val(uri);
            $('#show-' + slugfield).html(uri);
        },
        error: function () {
            console.log('failed to get an URI');
        }
    });
}

function makeUri(contenttypeslug, id, usesfields, slugfield, fulluri) {
    $(usesfields).each(function () {
        $('#' + this).on('propertychange.bolt input.bolt change.bolt', function () {
            var usesvalue = "";
            $(usesfields).each(function () {
                if ($("#" + this).is("select") && $("#" + this).hasClass("slug-text")) {
                    usesvalue += $("#" + this).val() ? $("#" + this).find("option[value=" + $("#" + this).val() + "]").text() : "";
                }
                else {
                    usesvalue += $("#" + this).val() || "";
                }
                usesvalue += " ";
            });
            clearTimeout(makeuritimeout);
            makeuritimeout = setTimeout(function () { makeUriAjax(usesvalue, contenttypeslug, id, slugfield, fulluri); }, 200);
        }).trigger('change.bolt');
    });
}

function stopMakeUri(usesfields) {

    $(usesfields).each(function () {
        $('#' + this).unbind('propertychange.bolt input.bolt change.bolt');
    });
    clearTimeout(makeuritimeout);

}

/**
 * Making the 'video embed' filetype work.
 */

var videoembedtimeout;

function bindVideoEmbedAjax(key) {
    // Embed endpoint http://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
    // @todo make less dependant on key..
    var endpoint = "http://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=",
        val = $('#video-' + key).val(),
        url = endpoint + encodeURI(val);

    // If val is emptied, clear the video fields..
    if (val.length < 2) {
        $('#video-' + key + '-html').val('');
        $('#video-' + key + '-width').val('');
        $('#video-' + key + '-height').val('');
        $('#video-' + key + '-ratio').val('');
        $('#video-' + key + '-text').html('');
        $('#myModal').find('.modal-body').html('');
        $('#video-' + key + '-author_name').val('');
        $('#video-' + key + '-author_url').val('');
        $('#video-' + key + '-title').val('');
        $('#thumbnail-' + key).html('');
        $('#video-' + key + '-thumbnail').val('');
        return;
    }

    $.getJSON(url, function (data) {
        if (data.html) {
            $('#video-' + key + '-html').val(data.html);
            $('#video-' + key + '-width').val(data.width);
            $('#video-' + key + '-height').val(data.height);
            $('#video-' + key + '-ratio').val(data.width / data.height);
            $('#video-' + key + '-text').html('"<b>' + data.title + '</b>" by ' + data.author_name);
            $('#myModal').find('.modal-body').html(data.html);
            $('#video-' + key + '-author_name').val(data.author_name);
            $('#video-' + key + '-author_url').val(data.author_url);
            $('#video-' + key + '-title').val(data.title);
        }

        if (data.thumbnail_url) {
            $('#thumbnail-' + key).html("<img src='" + data.thumbnail_url + "' width='160' height='120'>");
            $('#video-' + key + '-thumbnail').val(data.thumbnail_url);
        }
    });
}

function bindVideoEmbed(key) {
    $('#video-' + key).bind('propertychange input', function () {
        clearTimeout(videoembedtimeout);
        videoembedtimeout = setTimeout(function () { bindVideoEmbedAjax(key); }, 400);
    });

    $('#video-' + key + '-width').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-height').val(Math.round($('#video-' + key + '-width').val() / $('#video-' + key + '-ratio').val()));
        }
    });

    $('#video-' + key + '-height').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-width').val(Math.round($('#video-' + key + '-height').val() * $('#video-' + key + '-ratio').val()));
        }
    });
}

var geotimeout;

function updateGeoCoords(key) {
    var markers = $.goMap.getMarkers(),
        marker = markers[0].split(","),
        geocoder,
        latlng;

    if (typeof(marker[0] !== "undefined")) {
        $('#' + key + '-latitude').val(marker[0]);
        $('#' + key + '-longitude').val(marker[1]);

        // update the 'according to Google' info:
        geocoder = new google.maps.Geocoder();
        latlng = new google.maps.LatLng(marker[0], marker[1]);

        geocoder.geocode({'latLng': latlng}, function (results, status) {
            $('#' + key + '-reversegeo').html(results[0].formatted_address);
            $('#' + key + '-formatted_address').val(results[0].formatted_address);
        });
    }
}

function bindGeoAjax(key) {
    var address = $("#" + key + "-address").val();

    // If address is emptied, clear the address fields.
    if (address.length < 2) {
        $('#' + key + '-latitude').val('');
        $('#' + key + '-longitude').val('');
        $('#' + key + '-reversegeo').html('');
        $('#' + key + '-formatted_address').val('');
        return;
    }

    $.goMap.setMap({address: address});
    $.goMap.setMarker('pinmarker', {address: address});

    setTimeout(function () { updateGeoCoords(key); }, 500);
}

function bindGeolocation(key, latitude, longitude) {
    latitude = parseFloat(latitude);
    longitude = parseFloat(longitude);

    // Default location is Two Kings, for now.
    if (latitude === 0 || isNaN(latitude)) {
        latitude = 52.08184;
    }
    if (longitude === 0 || isNaN(longitude)) {
        longitude = 4.292368;
    }

    $("#" + key + "-address").bind('propertychange input', function () {
        clearTimeout(geotimeout);
        geotimeout = setTimeout(function () { bindGeoAjax(key); }, 800);
    });

    $("#map-" + key).goMap({
        latitude: latitude,
        longitude: longitude,
        zoom: 15,
        maptype: 'ROADMAP',
        disableDoubleClickZoom: true,
        addMarker: false,
        icon: apppath + 'view/img/pin_red.png',
        markers: [{
            latitude: latitude,
            longitude: longitude,
            id: 'pinmarker',
            title: 'Pin',
            draggable: true
        }]
    });

    // Handler for when the marker is dropped..
    $.goMap.createListener({type:'marker', marker:'pinmarker'}, 'mouseup', function () { updateGeoCoords(key); });
}

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
            var prelist = $.parseJSON($('#' + this.id).val());
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

        var $list = $('#imagelist-' + this.id + ' .list'),
            index = 0;

        $list.html('');
        _.each(this.list.models, function (image) {
            image.set('id', index++);
            var html = "<div data-id='" + image.get('id') + "' class='ui-state-default'>" +
                "<img src='" + path + "../thumbs/60x40/" + image.get('filename') + "' width=60 height=40>" +
                "<input type='text' value='" + _.escape(image.get('title'))  + "'>" +
                "<a href='#'><i class='fa fa-times'></i></a></div>";
            $list.append(html);
        });
        if (this.list.models.length === 0) {
            $list.append("<p>No images in the list, yet.</p>");
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
                var fileTypes = $('#fileupload-' + contentkey).attr('accept');

                if (typeof fileTypes !== 'undefined') {
                    var pattern = new RegExp("\.(" + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ")$", "i");
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            var message = "Oops! There was an error uploading the image. Make sure that the file " +
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

/**
 * Backbone object for collapsable sidebar.
 */
var Sidebar = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {
        // Do this, only if the sidebar is visible. (not when in small-responsive view)
        if ($('#navpage-secondary').is(':visible')) {

            // Note: It might seem easier to do this with a simple .popover, but we
            // shouldn't. People using keyboard access will not appreciate the menu timing
            // out and disappearing after a split-second of losing focus.
            $('#navpage-secondary a.menu-pop').on('mouseover focus', function () {
                $('#navpage-secondary a.menu-pop').not(this).popover('hide');
                $(this).popover('show');
            });

            // Likewise, we need to distinct events, to hide the sidebar's popovers:
            // One for 'mouseleave' on the sidebar itself, and one for keyboard 'focus'
            // on the items before and after.
            $('#navpage-secondary').on('mouseleave', function () {
                window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').popover('hide');
                }, 500);
            });
            $('.nav-secondary-collapse a, .nav-secondary-dashboard a').on('focus', function () {
                $('#navpage-secondary a.menu-pop').popover('hide');
            });

        }

        // set up 'fixlength'
        window.setTimeout(function () { sidebar.fixlength(); }, 500);

    },

    /*
     * Make sure the sidebar is as long as the document height. Also: Typecasting! love it or hate it!
     */
    fixlength: function () {
        var documentheight = $('#navpage-content').height() + 22;
        if (documentheight > $('#navpage-secondary').height()) {
            $('#navpage-secondary').height(documentheight + "px");
            window.setTimeout(function () { sidebar.fixlength(); }, 500);
        }
    },

    /**
     * Hide / show subitems in the sidebar for mobile devices.
     *
     * @param {string} name
     */
    showSidebarItems: function (name) {
        sidebar.closePopOvers();
        // Check if the "hamburger menu" is actually visible. If not, we're not on mobile
        // or tablet, and we should just redirect to the first link, to prevent confusion.
        if (!$('.navbar-toggle').is(':visible')) {
            window.location.href = $('#navpage-secondary .submenu-' + name).find('a').first().attr('href');
        } else {
            if ($('#navpage-secondary .submenu-' + name).hasClass('show')) {
                $('#navpage-secondary .submenu-' + name).removeClass('show');
            } else {
                $('#navpage-secondary .submenu').removeClass('show');
                $('#navpage-secondary .submenu-' + name).addClass('show');
            }
        }
    },

    /**
     * Collapse secondary navigation to icon only design
     */
    collapse: function () {
        sidebar.closePopOvers();
        $('#navpage-wrapper').removeClass('nav-secondary-opened').addClass('nav-secondary-collapsed');
        // We add the '-hoverable' class to make sure the sidebar _first_ collapses, and only _then_
        // can be opened by hovering on it.
        setTimeout(function () {
            $('#navpage-wrapper').addClass('nav-secondary-collapsed-hoverable');
        }, 300);
        $.cookie('sidebar', 'collapsed', { expires: 21, path: '/' });
    },

    /**
     * Expand secondary navigation to icon full width design
     */
    expand: function () {
        sidebar.closePopOvers();
        $('#navpage-wrapper').removeClass('nav-secondary-collapsed nav-secondary-opened nav-secondary-collapsed-hoverable');
        $.removeCookie('sidebar', {path: '/'});
    },

    /**
     * Show/hide secondary navigation
     */
    toggle: function () {
        var wrapper = $('#navpage-wrapper');
        if (wrapper.hasClass('nav-secondary-opened')) {
            wrapper.removeClass('nav-secondary-opened nav-secondary-collapsed');
        } else {
            wrapper.removeClass('nav-secondary-collapsed').addClass('nav-secondary-opened');
        }
    },

    closePopOvers: function () {
        $('#navpage-secondary a.menu-pop').popover('hide');
    }
});

/**
 * Backbone object for all file actions functionality.
 */
var Files = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {
    },

    /**
     * Rename a file.
     *
     * @param {string} promptQuestionString Translated version of "Which file to rename?".
     * @param {string} namespace            The namespace.
     * @param {string} parentPath           Parent path of the folder to rename.
     * @param {string} oldName              Old name of the file to be renamed.
     * @param {object} element              The object that calls this function, usually of type HTMLAnchorElement)
     */
    renameFile: function (promptQuestionString, namespace, parentPath, oldName, element)
    {
        var newName = window.prompt(promptQuestionString, oldName);

        if (!newName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'renamefile',
            type: 'POST',
            data: {
                namespace: namespace,
                parent:  parentPath,
                oldname: oldName,
                newname: newName
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong renaming this file!');
            }
        });
    },

    /**
     * Delete a file from the server.
     *
     * @param {string} namespace
     * @param {string} filename
     * @param {object} element
     */
    deleteFile: function (namespace, filename, element) {

        if (!confirm('Are you sure you want to delete ' + filename + '?')) {
            return;
        }

        $.ajax({
            url: asyncpath + 'deletefile',
            type: 'POST',
            data: {
                namespace: namespace,
                filename: filename
            },
            success: function (result) {
                console.log('Deleted file ' + filename  + ' from the server');

                // If we are on the files table, remove image row from the table, as visual feedback
                if (element !== null) {
                    $(element).closest('tr').slideUp();
                }

                // TODO delete from Stack if applicable

            },
            error: function () {
                console.log('Failed to delete the file from the server');
            }
        });
    },

    duplicateFile: function (namespace, filename) {
        $.ajax({
            url: asyncpath + 'duplicatefile',
            type: 'POST',
            data: {
                namespace: namespace,
                filename: filename
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong duplicating this file!');
            }
        });
    }

});

/**
 * Backbone object for all Stack-related functionality.
 */
var Stack = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {
        this.bindEvents();
    },

    bindEvents: function () {

        bindFileUpload('stack');

        // In the modal dialog, to navigate folders..
        $('#selectImageModal-stack').on('click', '.folder', function (e) {
            e.preventDefault();
            alert('hoi');
            $('#selectImageModal-stack .modal-content').load($(this).attr('href'));
        });

    },

    /**
     * Add a file to our simple Stack.
     *
     * @param {string} filename
     * @param {object} element
     */
    addToStack: function (filename, element) {

        var ext = filename.substr(filename.lastIndexOf('.') + 1).toLowerCase(),
            type;

        if (ext === "jpg" || ext === "jpeg" || ext === "png" || ext === "gif") {
            type = "image";
        } else {
            type = "other";
        }

        // We don't need 'files/' in the path. Accept input with or without it, but strip it out here.
        filename = filename.replace(/files\//ig, '');

        $.ajax({
            url: asyncpath + 'addstack/' + filename,
            type: 'GET',
            success: function (result) {
                console.log('Added file ' + filename  + ' to stack');

                // Move all current items one down, and remove the last one
                var stack = $('#stackholder div.stackitem'),
                    i,
                    item,
                    html;

                for (i=stack.length; i>=1; i--) {
                    item = $("#stackholder div.stackitem.item-" + i);
                    item.addClass('item-' + (i + 1)).removeClass('item-' + i);
                }
                if ($("#stackholder div.stackitem.item-8").is('*')) {
                    $("#stackholder div.stackitem.item-8").remove();
                }

                // If added via a button on the page, disable the button, as visual feedback
                if (element !== null) {
                    $(element).addClass('disabled');
                }

                // Insert new item at the front.
                if (type === "image") {
                    html = $('#protostack div.image').clone();
                    $(html).find('img').attr('src', path + "../thumbs/100x100c/" + encodeURI(filename) );
                } else {
                    html = $('#protostack div.other').clone();
                    $(html).find('strong').html(ext.toUpperCase());
                    $(html).find('small').html(filename);
                }
                $('#stackholder').prepend(html);

                // If the "empty stack" notice was showing, remove it.
                $('.nostackitems').remove();

            },
            error: function () {
                console.log('Failed to add file to stack');
            }
        });
    },

    selectFromPulldown: function (key, filename) {
        console.log("select: ", key + " = " + filename);

        // For "normal" file and image fields..
        if ($('#field-' + key).is('*')) {
            $('#field-' + key).val(filename);
        }

        // For Imagelist fields. Check if imagelist[key] is an object.
        if (typeof imagelist === "object" && typeof imagelist[key] === "object") {
            imagelist[key].add(filename, filename);
        }

        // For Filelist fields. Check if filelist[key] is an object.
        if (typeof filelist === "object" && typeof filelist[key] === "object") {
            filelist[key].add(filename, filename);
        }

        // If the field has a thumbnail, set it.
        if ($('#thumbnail-' + key).is('*')) {
            src = path + "../thumbs/120x120c/" + encodeURI(filename);
            $('#thumbnail-' + key).html("<img src='" + src + "' width='120' height='120'>");
        }

        // Close the modal dialog, if this image/file was selected through one.
        if ($('#selectModal-' + key).is('*')) {
            $('#selectModal-' + key).modal('hide');
        }

        // If we need to place it on the stack as well, do so.
        if (key === "stack") {
            stack.addToStack(filename);
        }

    },

    changeFolder: function (key, foldername) {
        $('#selectModal-' + key + ' .modal-content').load(foldername);
    }

});

/**
 * This backbone model cares about folder actions within /files in the backend.
 */
var Folders = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {
    },

    /**
     * Create a folder.
     *
     * @param {string} promptQuestionString Translated version of "What's the new filename?".
     * @param {string} namespace
     * @param {string} parentPath Parent path of the folder to create.
     * @param {object} element
     */
    create: function (promptQuestionString, namespace, parentPath, element)
    {
        var newFolderName = window.prompt(promptQuestionString);

        if (!newFolderName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'folder/create',
            type: 'POST',
            data: {
                parent: parentPath,
                foldername: newFolderName,
                namespace: namespace
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong renaming this folder!');
            }
        });
    },

    /**
     * Rename a folder.
     *
     * @param {string} promptQuestionString Translated version of "Which file to rename?".
     * @param {string} namespace
     * @param {string} parentPath           Parent path of the folder to rename.
     * @param {string} oldFolderName        Old name of the folder to be renamed.
     * @param {object} element
     */
    rename: function (promptQuestionString, namespace, parentPath, oldFolderName, element)
    {
        var newFolderName = window.prompt(promptQuestionString, oldFolderName);

        if (!newFolderName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'folder/rename',
            type: 'POST',
            data: {
                namespace: namespace,
                parent: parentPath,
                oldname: oldFolderName,
                newname: newFolderName
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong renaming this folder!');
            }
        });
    },

    /**
     * Remove a folder.
     *
     * @param {string} namespace
     * @param {string} parentPath Parent path of the folder to remove.
     * @param {string} folderName Name of the folder to remove.
     * @param {object} element
     */
    remove: function (namespace, parentPath, folderName, element)
    {
        $.ajax({
            url: asyncpath + 'folder/remove',
            type: 'POST',
            data: {
                namespace: namespace,
                parent: parentPath,
                foldername: folderName
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong renaming this folder!');
            }
        });
    }
});

var init = {

    /*
     * Auto-update the 'latest activity' widget.
     *
     * @returns {undefined}
     */
    activityWidget: function () {
        if ($('#latestactivity').is('*')) {
            setTimeout(function () {
                updateLatestActivity();
            }, 20 * 1000);
        }
    },

    /**
     * Any link (or clickable <i>-icon) with a class='confirm' gets a confirmation dialog.
     *
     * @returns {undefined}
     */
    confirmationDialogs: function () {
        $('.confirm').on('click', function () {
            return confirm($(this).data('confirm'));
        });
    },

    /*
     * Dashboard listing checkboxes
     *
     * @returns {undefined}
     */
    dashboardCheckboxes: function () {
        // Check all checkboxes
        $(".dashboardlisting tr th:first-child input:checkbox").click(function () {
            var checkedStatus = this.checked;
            $(".dashboardlisting tr td:first-child input:checkbox").each(function () {
                this.checked = checkedStatus;
                if (checkedStatus === this.checked) {
                    $(this).closest('table tbody tr').removeClass('row-checked');
                }
                if (this.checked) {
                    $(this).closest('table tbody tr').addClass('row-checked');
                }
            });
        });
        // Check if any records in the overview have been checked, and if so: show action buttons
        $('.dashboardlisting input:checkbox').click(function () {
            var aItems = getSelectedItems();
            if (aItems.length >= 1) {
                // if checked
                $('a.checkchosen').removeClass('disabled');
                $('a.showifchosen').show();
            } else {
                // if none checked
                $('a.checkchosen').addClass('disabled');
                $('a.showifchosen').hide();
            }
        });
        // Delete chosen Items
        $("a.deletechosen").click(function (e) {
            e.preventDefault();
            var aItems = getSelectedItems();

            if (aItems.length < 1) {
                bootbox.alert("Nothing chosen to delete");
            } else {
                var notice = "Are you sure you wish to <strong>delete " + (aItems.length=== 1 ? "this record" : "these records") + "</strong>? There is no undo.";
                bootbox.confirm(notice, function (confirmed) {
                    $(".alert").alert();
                    if (confirmed === true) {
                        $.each(aItems, function (index, id) {
                            // delete request
                            $.ajax({
                                url: $('#baseurl').attr('value') + 'content/deletecontent/' + $('#item_' + id).closest('table').data('contenttype') + '/' + id + '?token=' + $('#item_' + id).closest('table').data('token'),
                                type: 'get',
                                success: function (feedback) {
                                    $('#item_' + id).hide();
                                    $('a.deletechosen').hide();
                                }
                            });
                        });
                    }
                });
            }
        });
    },

    /**
     * Helper to make things like '<button data-action="eventView.load()">' work
     *
     * @returns {undefined}
     */
    dataActions: function () {
        // Unbind the click events, with the 'action' namespace.
        $('button, input[type=button], a').off('click.action');

        // Bind the click events, with the 'action' namespace.
        $('[data-action]').on('click.action', function (e) {
            var action = $(this).data('action');
            if (typeof action !== "undefined" && action !== "") {
                eval(action);
                e.stopPropagation();
                e.preventDefault();
            }
        })
        // Prevent propagation to parent's click handler from anchor in popover.
        .on('click.popover', '.popover', function (e) {
            e.stopPropagation();
        });
    },

    /*
     * Add Date and Timepickers.
     *
     * @returns {undefined}
     */
    dateTimePickers: function () {
        $(".datepicker").datepicker({
            dateFormat: "DD, d MM yy"
        });
    },

    /*
     * Render any deferred widgets, if any.
     *
     * @returns {undefined}
     */
    deferredWidgets: function () {
        $('div.widget').each(function () {
            if (typeof $(this).data('defer') === 'undefined') {
                return;
            }

            var key = $(this).data('key');

            $.ajax({
                url: asyncpath + 'widget/' + key,
                type: 'GET',
                success: function (result) {
                    $('#widget-' + key).html(result);
                },
                error: function () {
                    console.log('failed to get widget');
                }
            });
        });
    },

    /*
     * Smarter dropdowns/dropups based on viewport height.
     * Based on: https://github.com/twbs/bootstrap/issues/3637#issuecomment-9850709
     *
     * @returns {undefined}
     */
    dropDowns: function () {
        $('[data-toggle="dropdown"]').each(function (index, item) {
            var mouseEvt;
            if (typeof event === 'undefined') {
                $(item).parent().click(function (e) {
                    mouseEvt = e;
                });
            } else {
                mouseEvt = event;
            }
            $(item).parent().on('show.bs.dropdown', function (e) {

                // Prevent breakage on old IE.
                if (typeof mouseEvt === "undefined" || mouseEvt === null) {
                    return false;
                }

                var self = $(this).find('[data-toggle="dropdown"]'),
                    menu = self.next('.dropdown-menu'),
                    mousey = mouseEvt.pageY + 20,
                    menuHeight = menu.height(),
                    menuVisY = $(window).height() - (mousey + menuHeight), // Distance of element from the bottom of viewport
                    profilerHeight = 37; // The size of the Symfony Profiler Bar is 37px.

                // The whole menu must fit when trying to 'dropup', but always prefer to 'dropdown' (= default).
                if ((mousey - menuHeight) > 20 && menuVisY < profilerHeight) {
                    menu.css({
                        top: 'auto',
                        bottom: '100%'
                    });
                }
            });
        });
    },

    /*
     * Show 'dropzone' for jQuery file uploader.
     *
     * @returns {undefined}
     */
    dropZone: function () {
        // @todo make it prettier, and distinguish between '.in' and '.hover'.
        $(document).bind('dragover', function (e) {
            var dropZone = $('.dropzone'),
                timeout = window.dropZoneTimeout;
            if (!timeout) {
                dropZone.addClass('in');
            } else {
                clearTimeout(timeout);
            }
            if (e.target === dropZone[0]) {
                dropZone.addClass('hover');
            } else {
                dropZone.removeClass('hover');
            }
            window.dropZoneTimeout = setTimeout(function () {
                window.dropZoneTimeout = null;
                dropZone.removeClass('in hover');
            }, 100);
        });
    },

    /**
     * Initialize keyboard shortcuts:
     * - Click 'save' in Edit content screen.
     * - Click 'save' in "edit file" screen.
     *
     * @returns {undefined}
     */
    keyboardShortcuts: function () {
        function confirmExit() {
            if ($('form').hasChanged()) {
                return "You have unfinished changes on this page. If you continue without saving, you will lose these changes.";
            }
        }

        // We're on a regular 'edit content' page, if we have a sidebarsavecontinuebutton.
        // If we're on an 'edit file' screen,  we have a #saveeditfile
        if ($('#sidebarsavecontinuebutton').is('*') || $('#saveeditfile').is('*')) {

            // Bind ctrl-s and meta-s for saving..
            $('body, input').bind('keydown.ctrl_s keydown.meta_s', function (event) {
                event.preventDefault();
                $('form').watchChanges();
                $('#sidebarsavecontinuebutton, #saveeditfile').trigger('click');
            });

            // Initialize watching for changes on "the form".
            window.setTimeout(function () {
                $('form').watchChanges();
            }, 1000);

            // Initialize handler for 'closing window'
            window.onbeforeunload = confirmExit;
        }
    },

    /*
     * Initialize the Magnific popup shizzle. Fancybox is still here as a trigger, for backwards compatibility.
     */
    magnificPopup: function () {
        //
        $('.magnific, .fancybox').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            },
            disableOn: 400,
            closeBtnInside: true,
            enableEscapeKey: true,
            mainClass: 'mfp-with-zoom',
            zoom: {
                enabled: true,
                duration: 300,
                easing: 'ease-in-out',
                opener: function (openerElement) {
                    return openerElement.parent().parent().find('img');
                }
            }
        });
    },

    /*
     * Initialize 'moment' timestamps.
     *
     * @returns {undefined}
     */
    momentTimestamps: function () {
        if ($('.moment').is('*')) {
            updateMoments();
        }
    },

    /*
     * Omnisearch
     *
     * @returns {undefined}
     */
    omnisearch: function () {
        $('.omnisearch').select2({
            placeholder: '',
            minimumInputLength: 3,
            multiple: true, // this is for better styling …
            ajax: {
                url: asyncpath + "omnisearch",
                dataType: 'json',
                data: function (term, page) {
                    return {
                        q: term
                    };
                },
                results: function (data, page) {
                    var results = [];
                    $.each(data, function (index, item) {
                        results.push({
                            id: item.path,
                            path: item.path,
                            label: item.label,
                            priority: item.priority
                        });
                    });

                    return {results: results};
                }
            },
            formatResult: function (item) {
                var markup = '<table class="omnisearch-result"><tr>' +
                    '<td class="omnisearch-result-info">' +
                    '<div class="omnisearch-result-label">' + item.label + '</div>' +
                    '<div class="omnisearch-result-description">' + item.path + '</div>' +
                    '</td></tr></table>';

                return markup;
            },
            formatSelection: function (item) {
                window.location.href = item.path;

                return item.label;
            },
            dropdownCssClass: "bigdrop",
            escapeMarkup: function (m) {
                return m;
            }
        });
    },

    /*
     * Toggle options for showing / hiding the password input on the logon screen.
     *
     * @returns {undefined}
     */
    passwordInput: function () {
        $(".togglepass").on('click', function () {
            if ($(this).hasClass('show-password')) {
                $('input[name="password"]').attr('type', 'text');
                $('.togglepass.show-password').hide();
                $('.togglepass.hide-password').show();
            } else {
                $('input[name="password"]').attr('type', 'password');
                $('.togglepass.show-password').show();
                $('.togglepass.hide-password').hide();
            }
        });

        $('.login-forgot').bind('click', function (e) {
            $('.login-group, .password-group').slideUp('slow');
            $('.reset-group').slideDown('slow');
        });

        $('.login-remembered').bind('click', function (e) {
            $('.login-group, .password-group').slideDown('slow');
            $('.reset-group').slideUp('slow');
        });
    },

    /*
     * Initialize popovers.
     */
    popOvers: function () {
        $('.info-pop').popover({
            trigger: 'hover',
            delay: {
                show: 500,
                hide: 200
            }
        });
    },

    /*
     * ?
     */
    sortables: function () {
        $('tbody.sortable').sortable({
            items: 'tr',
            opacity: '0.5',
            axis: 'y',
            handle: '.sorthandle',
            update: function (e, ui) {
                serial = $(this).sortable('serialize');
                // sorting request
                $.ajax({
                    url: $('#baseurl').attr('value') + 'content/sortcontent/' + $(this).parent('table').data('contenttype'),
                    type: 'POST',
                    data: serial,
                    success: function (feedback) {
                        // do nothing
                    }
                });
            }
        });
    }

};
jQuery(function ($) {

    init.confirmationDialogs();
    init.magnificPopup();
    init.dataActions();
    window.setTimeout(function () {
        init.keyboardShortcuts();
    }, 1000);
    init.dropZone();
    init.popOvers();
    init.dateTimePickers();
    init.momentTimestamps();
    init.activityWidget();
    init.dropDowns();
    init.deferredWidgets();
    init.passwordInput();
    init.dashboardCheckboxes();
    init.sortables();
    init.omnisearch();

    // Initialize objects

    files = new Files();
    folders = new Folders();
    stack = new Stack();
    sidebar = new Sidebar();
});
