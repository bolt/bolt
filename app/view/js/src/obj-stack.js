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
