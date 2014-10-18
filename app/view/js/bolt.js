/**
 * These are Bolt's COMPILED JS files!
 * Do not edit these files, because all changes will be lost.
 * You can edit files in <js/src/*.js> and run 'grunt' to generate this file.
 */

/*jslint browser: true, devel: true, debug: false, indent: 4, maxlen: 120, nomen: true, plusplus: true, sloppy: true, unparam: true */
/*global $, jQuery, _, google, Backbone, bootbox, CKEDITOR, moment */

/*
 * Globals:
 * $:               jQuery
 * jQuery:          jQuery
 * _:               underscore.js
 * google:          ?
 * Backbone:        backbone.min.js
 * CKEDITOR:        ckeditor.js
 * bootbox          bootbox.min.js
 * moment:          moment.min.js
 *
 * bolt:            inline _page.twig
 */

/**********************************************************************************************************************/

var bolt = {};

// Don't break on browsers without console.log();
try {
    console.assert(1);
} catch(e) {
    console = {
        log: function () {},
        assert: function () {}
    };
}

/**********************************************************************************************************************/

/**
 * Helper to get all selected Items and return Array
 */
function getSelectedItems() {
    var aItems = [];
    $('.dashboardlisting input:checked').each(function () {
        if ($(this).parents('tr').attr('id')) {
            aItems.push($(this).parents('tr').attr('id').substr(5));
        }
    });
    console.log('getSelectedItems: ' + aItems);
    return aItems;
}

/**********************************************************************************************************************/

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
    $.get(bolt.paths.async + 'latestactivity', function (data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html($('#latesttemp').html());
    });

    setTimeout(function () {
        updateLatestActivity();
    }, 30 * 1000);
}

/**********************************************************************************************************************/

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
                    var filename, message;

                    if (file.error === undefined) {
                        filename = decodeURI(file.url).replace("files/", "");
                        $('#field-' + key).val(filename);
                        $('#thumbnail-' + key).html('<img src="' + bolt.paths.root + 'thumbs/200x150c/' +
                            encodeURI(filename) + '" width="200" height="150">');
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 1500);

                        // Add the uploaded file to our stack.
                        bolt.stack.addToStack(filename);

                    } else {
                        message = "Oops! There was an error uploading the file. Make sure the file is not " +
                            "corrupt, and that the 'files/'-folder is writable." +
                            "\n\n(error was: " + file.error + ")";

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

/**********************************************************************************************************************/

/**
 * Functions for working with the automagic URI/Slug generation.
 */

var makeuritimeout;

function makeUriAjax(text, contenttypeslug, id, slugfield, fulluri) {
    $.ajax({
        url: bolt.paths.async + 'makeuri',
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
                    usesvalue += $("#" + this).val() ?
                        $("#" + this).find("option[value=" + $("#" + this).val() + "]").text() : "";
                }
                else {
                    usesvalue += $("#" + this).val() || "";
                }
                usesvalue += " ";
            });
            clearTimeout(makeuritimeout);
            makeuritimeout = setTimeout(function () {
                makeUriAjax(usesvalue, contenttypeslug, id, slugfield, fulluri);
            }, 200);
        }).trigger('change.bolt');
    });
}

function stopMakeUri(usesfields) {
    $(usesfields).each(function () {
        $('#' + this).unbind('propertychange.bolt input.bolt change.bolt');
    });
    clearTimeout(makeuritimeout);
}

/**********************************************************************************************************************/

/**
 * Making the 'video embed' filetype work.
 */

var videoembedtimeout;

function bindVideoEmbedAjax(key) {
    // oembed endpoint http://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
    // @todo make less dependant on key.
    var endpoint = 'http://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=',
        val = $('#video-' + key).val(),
        url = endpoint + encodeURI(val);

    // If val is emptied, clear the video fields.
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
            $('#thumbnail-' + key).html('<img src="' + data.thumbnail_url + '" width="200" height="150">');
            $('#video-' + key + '-thumbnail').val(data.thumbnail_url);
        }
    });
}

function bindVideoEmbed(key) {
    $('#video-' + key).bind('propertychange input', function () {
        clearTimeout(videoembedtimeout);
        videoembedtimeout = setTimeout(function () {
            bindVideoEmbedAjax(key);
        }, 400);
    });

    $('#video-' + key + '-width').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-height').val(Math.round(
                $('#video-' + key + '-width').val() / $('#video-' + key + '-ratio').val()
            ));
        }
    });

    $('#video-' + key + '-height').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-width').val(Math.round(
                $('#video-' + key + '-height').val() * $('#video-' + key + '-ratio').val()
            ));
        }
    });
}

/**********************************************************************************************************************/

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

        geocoder.geocode({latLng: latlng}, function (results, status) {
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

    setTimeout(function () {
        updateGeoCoords(key);
    }, 500);
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
        geotimeout = setTimeout(function () {
            bindGeoAjax(key);
        }, 800);
    });

    $("#map-" + key).goMap({
        latitude: latitude,
        longitude: longitude,
        zoom: 15,
        maptype: 'ROADMAP',
        disableDoubleClickZoom: true,
        addMarker: false,
        icon: bolt.paths.app + 'view/img/pin_red.png',
        markers: [{
            latitude: latitude,
            longitude: longitude,
            id: 'pinmarker',
            title: 'Pin',
            draggable: true
        }]
    });

    // Handler for when the marker is dropped.
    $.goMap.createListener(
        {type: 'marker', marker: 'pinmarker'},
        'mouseup',
        function () {
            updateGeoCoords(key);
        }
    );
}

/**********************************************************************************************************************/

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
            prelist = $.parseJSON($('#' + this.id).val());
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
                var fileTypes = $('#fileupload-' + contentkey).attr('accept'),
                    pattern,
                    message;

                if (typeof fileTypes !== 'undefined') {
                    pattern = new RegExp("\\.(" + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ")$", "i");
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            message = "Oops! There was an error uploading the file. Make sure that the file " +
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

/**********************************************************************************************************************/

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

        var $list = $('#imagelist-' + this.id + ' .list'),
            index = 0;

        $list.html('');
        _.each(this.list.models, function (image) {
            image.set('id', index++);
            var html = "<div data-id='" + image.get('id') + "' class='ui-state-default'>" +
                "<img src='" + bolt.paths.bolt + "../thumbs/60x40/" + image.get('filename') + "' width=60 height=40>" +
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
                var fileTypes = $('#fileupload-' + contentkey).attr('accept'),
                    pattern,
                    message;

                if (typeof fileTypes !== 'undefined') {
                    pattern = new RegExp("\\.(" + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ")$", "i");
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            message = "Oops! There was an error uploading the image. Make sure that the file " +
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

/**********************************************************************************************************************/

/**
 * Backbone object for collapsable sidebar.
 */

var Sidebar = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {

        var menuTimeout = '';

        // Build popup menus
        $('#navpage-secondary a.menu-pop').each(function () {
            var name = $(this).attr('data-action'),
                menu = '';

            $('ul .submenu-' + name + ' li').each(function () {
                if ($(this).hasClass('subdivider')) {
                    menu += '<hr>';
                }
                menu += $(this).html().trim().replace(/[ \n]+/g, ' ').replace(/(>) | (<)/g, '$1$2');
            });

            $(this) .attr('data-html', true)
                    .attr('data-title', '')
                    .attr('data-action', 'bolt.sidebar.showSidebarItems("' + name + '")')
                    .attr('data-content', menu);
        });

        // Do this, only if the sidebar is visible. (not when in small-responsive view)
        if ($('#navpage-secondary').is(':visible')) {

            // Note: It might seem easier to do this with a simple .popover, but we
            // shouldn't. People using keyboard access will not appreciate the menu timing
            // out and disappearing after a split-second of losing focus.
            $('#navpage-secondary a.menu-pop').on('mouseover focus', function (e) {
                var item = this;

                window.clearTimeout(menuTimeout);
                menuTimeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                    $(item).popover('show');
                }, 400);
            });

            // We need two distinct events, to hide the sidebar's popovers:
            // One for 'mouseleave' on the sidebar itself, and one for keyboard
            // 'focus' on the items before and after.
            $('#navpage-secondary').on('mouseleave', function () {
                window.clearTimeout(menuTimeout);                
                menuTimeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').popover('hide');
                }, 800);
            });
            $('.nav-secondary-collapse a, .nav-secondary-dashboard a').on('focus', function () {
                window.clearTimeout(menuTimeout);
                $('#navpage-secondary a.menu-pop').popover('hide');
            });

        }

        // set up 'fixlength'
        window.setTimeout(function () { bolt.sidebar.fixlength(); }, 500);

    },

    /*
     * Make sure the sidebar is as long as the document height. Also: Typecasting! love it or hate it!
     */
    fixlength: function () {
        var documentheight = $('#navpage-content').height() + 34;
        if (documentheight > $('#navpage-secondary').height()) {
            $('#navpage-secondary').height(documentheight + "px");
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 300);
        } else {
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 3000);
        }
    },

    /**
     * Hide / show subitems in the sidebar for mobile devices.
     *
     * @param {string} name
     */
    showSidebarItems: function (name) {
        bolt.sidebar.closePopOvers();
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
        bolt.sidebar.closePopOvers();
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
        bolt.sidebar.closePopOvers();
        $('#navpage-wrapper').removeClass(
            'nav-secondary-collapsed nav-secondary-opened nav-secondary-collapsed-hoverable'
        );
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

/**********************************************************************************************************************/

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
            url: bolt.paths.async + 'renamefile',
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
            url: bolt.paths.async + 'deletefile',
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
            url: bolt.paths.async + 'duplicatefile',
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

/**********************************************************************************************************************/

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
            url: bolt.paths.async + 'addstack/' + filename,
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
                    $(html).find('img').attr('src', bolt.paths.bolt + "../thumbs/100x100c/" + encodeURI(filename) );
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

        // For Imagelist fields. Check if bolt.imagelist[key] is an object.
        if (typeof bolt.imagelist === 'object' && typeof bolt.imagelist[key] === 'object') {
            bolt.imagelist[key].add(filename, filename);
        }

        // For Filelist fields. Check if filelist[key] is an object.
        if (typeof bolt.filelist === 'object' && typeof bolt.filelist[key] === 'object') {
            bolt.filelist[key].add(filename, filename);
        }

        // If the field has a thumbnail, set it.
        if ($('#thumbnail-' + key).is('*')) {
            var src = bolt.paths.bolt + '../thumbs/200x150c/' + encodeURI(filename);
            $('#thumbnail-' + key).html('<img src="' + src + '" width="200" height="150">');
        }

        // Close the modal dialog, if this image/file was selected through one.
        if ($('#selectModal-' + key).is('*')) {
            $('#selectModal-' + key).modal('hide');
        }

        // If we need to place it on the stack as well, do so.
        if (key === "stack") {
            bolt.stack.addToStack(filename);
        }

        // Make sure the dropdown menu is closed. (Using the "blunt axe" method)
        $('.in,.open').removeClass('in open');

    },

    changeFolder: function (key, foldername) {
        $('#selectModal-' + key + ' .modal-content').load(foldername);
    }

});

/**********************************************************************************************************************/

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
            url: bolt.paths.async + 'folder/create',
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
            url: bolt.paths.async + 'folder/rename',
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
            url: bolt.paths.async + 'folder/remove',
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

/**********************************************************************************************************************/

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

    /*
     * Bind date field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindDate: function (data) {
        $('#' + data.id + '-date').on('change.bolt', function () {
            var date = $('#' + data.id + '-date').datepicker('getDate');
            $('#' + data.id).val($.datepicker.formatDate('yy-mm-dd', date));
        }).trigger('change.bolt');
    },

    /*
     * Bind datetime field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindDateTime: function (data) {
        $('#' + data.id + '-date, #' + data.id + '-time').on('change.bolt', function () {
            var date = $('#' + data.id + '-date').datepicker('getDate');
            var time = $.formatDateTime('hh:ii:00', new Date('2014/01/01 ' + $('#' + data.id + '-time').val()));
            $('#' + data.id).val($.datepicker.formatDate('yy-mm-dd', date) + ' ' + time);
        }).trigger('change.bolt');
    },

    /*
     * Bind editcontent
     *
     * @param {type} data
     * @returns {undefined}
     */
    bindEditContent: function (data) {
        // Save the page.
        $('#sidebarsavebutton').bind('click', function () {
            $('#savebutton').trigger('click');
        });

        $('#savebutton').bind('click', function () {
            // Reset the changes to the form.
            $('form').watchChanges();
        });

        // Handle "save and new".
        $('#sidebarsavenewbutton, #savenewbutton').bind('click', function () {
            // Reset the changes to the form.
            $('form').watchChanges();

            // Do a regular post, and expect to be redirected back to the "new record" page.
            var newaction = "?returnto=saveandnew";
            $('#editcontent').attr('action', newaction).submit();
        });

        // Clicking the 'save & continue' button either triggers an 'ajaxy' post, or a regular post which returns
        // to this page. The latter happens if the record doesn't exist yet, so it doesn't have an id yet.
        $('#sidebarsavecontinuebutton, #savecontinuebutton').bind('click', function (e) {
            e.preventDefault();

            var newrecord = data.newRecord;

            // Disable the buttons, to indicate stuff is being done.
            $('#sidebarsavecontinuebutton, #savecontinuebutton').addClass('disabled');
            $('p.lastsaved').text(data.msgSaving);

            if (newrecord) {
                // Reset the changes to the form.
                $('form').watchChanges();

                // New record. Do a regular post, and expect to be redirected back to this page.
                var newaction = "?returnto=new";
                $('#editcontent').attr('action', newaction).submit();
            } else {
                // Existing record. Do an 'ajaxy' post to update the record.

                // Reset the changes to the form.
                $('form').watchChanges();

                // Let the controller know we're calling AJAX and expecting to be returned JSON
                var ajaxaction = "?returnto=ajax";
                $.post(ajaxaction, $("#editcontent").serialize())
                    .done(function (data) {
                        $('p.lastsaved').html(data.savedon);
                        $('p.lastsaved').find('strong').text(moment().format('MMM D, HH:mm'));
                        $('p.lastsaved').find('time').attr('datetime', moment().format());
                        $('p.lastsaved').find('time').attr('title', moment().format());
                        updateMoments();

                        $('a#lastsavedstatus strong').html(
                            '<i class="fa fa-circle status-' + $("#statusselect option:selected").val() + '"></i> ' +
                            $("#statusselect option:selected").text()
                        );

                        // Update anything changed by POST_SAVE handlers
                        if ($.type(data) === 'object') {
                            $.each(data, function (index, item) {

                                // Things like images are stored in JSON arrays
                                if ($.type(item) === 'object') {
                                    $.each(item, function (subindex, subitem) {
                                        $(":input[name='" + index + "[" + subindex + "]']").val(subitem);
                                    });
                                } else {
                                    $(":input[name='" + index + "']").val(item);
                                }
                            });
                        }

                        // Reset the changes to the form from any updates we got from POST_SAVE changes
                        $('form').watchChanges();

                    })
                    .fail(function(){
                        $('p.lastsaved').text(data.msgNotSaved);
                    })
                    .always(function(){
                        // Re-enable buttons
                        $('#sidebarsavecontinuebutton, #savecontinuebutton').removeClass('disabled');
                    });
            }

        });

        // To preview the page, we set the target of the form to a new URL, and open it in a new window.
        $('#previewbutton, #sidebarpreviewbutton').bind('click', function (e) {
            e.preventDefault();
            var newaction = data.pathsRoot + "preview/" + data.singularSlug;
            $('#editcontent').attr('action', newaction).attr('target', '_blank').submit();
            $('#editcontent').attr('action', '').attr('target', "_self");
        });

        // Only if we have grouping tabs.
        if (data.hasGroups) {
            // Filter for tabs
            var allf = $('.tabgrouping');
            allf.hide();
            // Click function
            $(".filter").click(function() {
                var customType = $(this).data('filter');
                allf
                    .hide()
                    .filter(function () {
                        return $(this).data('tab') === customType;
                    })
                    .show();
                $('#filtertabs li').removeClass('active');
                $(this).parent().attr('class', 'active');
            });

            $(document).ready(function () {
                $('#filtertabs li a:first').trigger('click');
            });
        }
    },

    /*
     * Bind editfile field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditFile: function (data) {
        $('#saveeditfile').bind('click', function (e) {
            // Reset the handler for checking changes to the form.
            window.onbeforeunload = null;
        });

        var editor = CodeMirror.fromTextArea(document.getElementById('form_contents'), {
            lineNumbers: true,
            autofocus: true,
            tabSize: 4,
            indentUnit: 4,
            indentWithTabs: false,
            readOnly: data.readonly
        });

        var newheight = $(window).height() - 312;
        if (newheight < 200) {
            newheight = 200;
        }

        editor.setSize(null, newheight);
    },

    /*
     * Bind editlocale field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditLocale: function (data) {
        var editor = CodeMirror.fromTextArea(document.getElementById('form_contents'), {
            lineNumbers: true,
            autofocus: true,
            tabSize: 4,
            indentUnit: 4,
            indentWithTabs: false,
            readOnly: data.readonly
        });

        editor.setSize(null, $(window).height() - 276);
    },

    /*
     * Bind filebrowser
     */
    bindFileBrowser: function () {
        console.log("bindFileBrowser");
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        })

        var getUrlParam = function(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return (match && match.length > 1) ? match[1] : null;
        };
        var funcNum = getUrlParam('CKEditorFuncNum');

        $('a.filebrowserCallbackLink').bind('click', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            window.opener.CKEDITOR.tools.callFunction(funcNum, url);
            window.close();
        });

        $('a.filebrowserCloseLink').bind('click', function () {
            window.close();
        })
    },

    bindCkFileSelect: function (data) {
        var getUrlParam = function (paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return (match && match.length > 1) ? match[1] : null;
        };

        var funcNum = getUrlParam('CKEditorFuncNum');
        $('a.filebrowserCallbackLink').bind('click', function (event) {
            event.preventDefault();
            var url = $(this).attr('href');
            window.opener.CKEDITOR.tools.callFunction(funcNum, url);
            window.close();
        });
    },

    /*
     * Bind prefill
     */
    bindPrefill: function () {
        $('#check-all').on('click', function() {
            // because jQuery is being retarded.
            // See: http://stackoverflow.com/questions/5907645/jquery-chrome-and-checkboxes-strange-behavior
            $("#form_contenttypes :checkbox").removeAttr('checked').trigger('click')
        });
        $('#uncheck-all').on('click', function() {
            $("#form_contenttypes :checkbox").removeAttr('checked');
        });
    },

    /*
     * Bind slug field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindSlug: function (data) {
        $('.sluglocker').bind('click', function () {
            if ($('.sluglocker i').hasClass('fa-lock')) {
                // "unlock" if it's currently empty, _or_ we've confirmed that we want to do so.
                if (data.isEmpty || confirm(data.messageUnlock)) {
                    $('.sluglocker i').removeClass('fa-lock').addClass('fa-unlock');
                    makeUri(data.slug, data.contentId, data.uses, data.key, false);
                }
            } else {
                $('.sluglocker i').addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(data.uses);
            }
        });

        $('.slugedit').bind('click', function () {
            newslug = prompt(data.messageSet, $('#show-' + data.key).text());
            if (newslug) {
                $('.sluglocker i').addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(data.uses);
                makeUriAjax(newslug, data.slug, data.contentId, data.key, false);
            }
        });

        if (data.isEmpty) {
            $('.sluglocker').trigger('click');
        }
    },

    /*
     * Bind ua
     */
    bindUserAgents: function () {
        $('.useragent').each(function () {
            var parser = new UAParser($(this).data('ua')),
                result = parser.getResult();

            $(this).html(result.browser.name + " " + result.browser.major + " / " + result.os.name + " " + result.os.version);
        });
    },

    /*
     * Bind video field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindVideo: function (data) {
        bindVideoEmbed(data.key);
    },

    /*
     * Initialise CKeditor instances.
     */
    ckeditor: function () {
        CKEDITOR.editorConfig = function (config) {
            var key,
                custom,
                set = bolt.ckeditor;

            var basicStyles = ['Bold', 'Italic'];
            var linkItems = ['Link', 'Unlink'];
            var toolItems = [ 'RemoveFormat', 'Maximize', '-', 'Source'];
            var paragraphItems = ['NumberedList', 'BulletedList', 'Indent', 'Outdent'];

            if (set.underline) {
                basicStyles = basicStyles.concat('Underline');
            }
            if (set.strike) {
                basicStyles = basicStyles.concat('Strike');
            }
            if (set.anchor) {
                linkItems = linkItems.concat('-', 'Anchor');
            }
            if (set.specialchar) {
                toolItems = ['SpecialChar', '-'].concat(toolItems);
            }
            if (set.blockquote) {
                paragraphItems = paragraphItems.concat('-', 'Blockquote');
            }

            config.language = set.language;
            config.uiColor = '#DDDDDD';
            config.resize_enabled = true;
            config.entities = false;
            config.extraPlugins = 'codemirror';
            config.toolbar = [
                { name: 'styles', items: ['Format'] },
                { name: 'basicstyles', items: basicStyles }, // ['Bold', 'Italic', 'Underline', 'Strike']
                { name: 'paragraph', items: paragraphItems },
                { name: 'links', items: linkItems }
            ];


            if (set.subsuper) {
                config.toolbar = config.toolbar.concat({
                    name: 'subsuper', items: ['Subscript', 'Superscript']
                });
            }
            if (set.images) {
                config.toolbar = config.toolbar.concat({
                    name: 'image', items: ['Image']
                });
            }
            if (set.embed) {
                config.extraPlugins += ',oembed,widget';
                config.oembed_maxWidth = '853';
                config.oembed_maxHeight = '480';
                config.toolbar = config.toolbar.concat({
                    name: 'embed', items: ['oembed']
                });
            }

            if (set.tables) {
                config.toolbar = config.toolbar.concat({
                    name: 'table', items: ['Table']
                });
            }
            if (set.align) {
                config.toolbar = config.toolbar.concat({
                    name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                });
            }
            if (set.fontcolor) {
                config.toolbar = config.toolbar.concat({
                    name: 'colors', items: ['TextColor', 'BGColor']
                });
            }

            if (set.codesnippet) {
                config.toolbar = config.toolbar.concat({
                    name: 'code', items: ['-', 'CodeSnippet']
                });
            }

            config.toolbar = config.toolbar.concat({
                name: 'tools', items: toolItems
            });

            config.height = 250;
            config.autoGrow_onStartup = true;
            config.autoGrow_minHeight = 150;
            config.autoGrow_maxHeight = 400;
            config.autoGrow_bottomSpace = 24;
            config.removePlugins = 'elementspath';
            config.resize_dir = 'vertical';

            if (set.filebrowser) {
                if (set.filebrowser.browseUrl) {
                    config.filebrowserBrowseUrl = set.filebrowser.browseUrl;
                }
                if (set.filebrowser.imageBrowseUrl) {
                    config.filebrowserImageBrowseUrl = set.filebrowser.imageBrowseUrl;
                }
                if (set.filebrowser.uploadUrl) {
                    config.filebrowserUploadUrl = set.filebrowser.uploadUrl;
                }
                if (set.filebrowser.imageUploadUrl) {
                    config.filebrowserImageUploadUrl = set.filebrowser.imageUploadUrl;
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

            // Parse override settings from config.yml
            for (key in set.ck) {
                if (set.ck.hasOwnProperty(key)) {
                     config[key] = set.ck[key];
                }
            }

            // Parse override settings from field in contenttypes.yml
            custom = $('textarea[name=' + this.name + ']').data('ckconfig');
            for (key in custom) {
                if (custom.hasOwnProperty(key)) {
                    config[key] = custom[key];
                }
            }
        };
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
            var aItems = getSelectedItems(),
                notice;

            if (aItems.length < 1) {
                bootbox.alert("Nothing chosen to delete");
            } else {
                notice = "Are you sure you wish to <strong>delete " +
                    (aItems.length=== 1 ? "this record" : "these records") + "</strong>? There is no undo.";
                bootbox.confirm(notice, function (confirmed) {
                    $(".alert").alert();
                    if (confirmed === true) {
                        $.each(aItems, function (index, id) {
                            // Delete request
                            $.ajax({
                                url: $('#baseurl').attr('value') + 'content/deletecontent/' +
                                    $('#item_' + id).closest('table').data('contenttype') + '/' + id + '?token=' +
                                    $('#item_' + id).closest('table').data('token'),
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
            var action = $(this).attr('data-action');
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
                url: bolt.paths.async + 'widget/' + key,
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
                    menuVisY = $(window).height() - (mousey + menuHeight), // Distance from the bottom of viewport
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
     * Bind geolocation
     */
    geolocation: function () {
        $('input[data-geolocation]').each(function (item) {
            var data = $(this).data('geolocation');

            bindGeolocation(data.key, data.lat, data.lon);
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
                return "You have unfinished changes on this page. " +
                    "If you continue without saving, you will lose these changes.";
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
                url: bolt.paths.async + 'omnisearch',
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
            $('.login-group, .password-group').hide();
            $('.reset-group').show();
        });

        $('.login-remembered').bind('click', function (e) {
            $('.login-group, .password-group').show();
            $('.reset-group').hide();
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

    uploads: function () {
        $('input[data-upload]').each(function (item) {
            var data = $(this).data('upload'),
                accept = $(this).attr('accept').replace(/\./g, ''),
                autocomplete_conf;

            switch (data.type) {
                case 'Image':
                case 'File':
                    bindFileUpload(data.key);

                    autocomplete_conf = {
                        source: bolt.paths.async + 'filesautocomplete?ext=' + encodeURIComponent(accept),
                        minLength: 2
                    };
                    if (data.type === 'image') {
                        autocomplete_conf.close = function () {
                            var path = $('#field-' + data.key).val(),
                                url;

                            if (path) {
                                url = bolt.paths.root +'thumbs/' + data.width + 'x' + data.height + 'c/' + encodeURI(path);
                            } else {
                                url = bolt.paths.app + 'view/img/default_empty_4x3.png';
                            }
                            $('#thumbnail-' + data.key).html(
                                '<img src="'+ url + '" width="' + data.width + '" height="' + data.height + '">'
                            );
                        };
                    }
                    $('#field-' + data.key).autocomplete(autocomplete_conf);
                    break;

                case 'ImageList':
                    bolt.imagelist[data.key] = new ImagelistHolder({id: data.key});
                    break;

                case 'FileList':
                    bolt.filelist[data.key] = new FilelistHolder({id: data.key});
                    break;
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
                var serial = $(this).sortable('serialize');
                // Sorting request
                $.ajax({
                    url: $('#baseurl').attr('value') + 'content/sortcontent/' +
                        $(this).parent('table').data('contenttype'),
                    type: 'POST',
                    data: serial,
                    success: function (feedback) {
                        // Do nothing
                    }
                });
            }
        });
    }

};

/**********************************************************************************************************************/


jQuery(function ($) {
    // Get configuration
    bolt = $('script[data-config]').first().data('config');

    // Initialize objects
    bolt.files = new Files();
    bolt.folders = new Folders();
    bolt.stack = new Stack();
    bolt.sidebar = new Sidebar();
    bolt.imagelist = [];
    bolt.filelist = [];

    // Initialisation
    init.ckeditor();
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
    init.uploads();
    init.geolocation();

    $('[data-bind]').each(function () {
        var data = $(this).data('bind');
        //console.log('Binding: ' + data.bind);

        switch (data.bind) {
            case 'date': init.bindDate(data); break;
            case 'datetime': init.bindDateTime(data); break;
            case 'editcontent': init.bindEditContent(data); break;
            case 'editfile': init.bindEditFile(data); break;
            case 'editlocale': init.bindEditLocale(data); break;
            case 'filebrowser': init.bindFileBrowser(); break;
            case 'ckfileselect': init.bindCkFileSelect(); break;
            case 'prefill': init.bindPrefill(); break;
            case 'slug': init.bindSlug(data); break;
            case 'useragents': init.bindUserAgents(); break;
            case 'video': init.bindVideo(data); break;
            default: console.log('Binding ' + data.bind + ' failed!');
        }
    });
});

//# sourceMappingURL=bolt.js.map