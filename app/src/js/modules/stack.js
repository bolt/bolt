/**
 * Stack-related functionality.
 *
 * @mixin
 * @namespace Bolt.stack
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Bolt.stack mixin container.
     *
     * @private
     * @type {Object}
     */
    var stack = {};


    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.stack
     */
    stack.init = function () {
        bindFileUpload('stack');

        // In the modal dialog, to navigate folders.
        $('#selectImageModal-stack').on('click', '.folder', function (e) {
            e.preventDefault();
            $('#selectImageModal-stack .modal-content').load($(this).attr('href'));
        });

        // Set data actions for async file modals.
        var elements = $([]);
        $('[data-toggle="modal"]').each(function () {
            elements = elements.add($($(this).data('target')));
        });

        $(elements).on(
            'loaded.bs.modal',
            function (e) {
                bolt.actions.init();
            }
        );

    };

    /**
     * Add a file to the stack.
     *
     * @static
     * @function addToStack
     * @memberof Bolt.stack
     *
     * @param {string} filename - The name of the file to add
     * @param {object} element - The object that calls this function
     */
    stack.addToStack = function (filename, element) {
        var ext = filename.substr(filename.lastIndexOf('.') + 1).toLowerCase(),
            type;

        if (ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif') {
            type = 'image';
        } else {
            type = 'other';
        }

        // We don't need 'files/' in the path. Accept input with or without it, but strip it out here.
        filename = filename.replace(/files\//ig, '');

        $.ajax({
            url: bolt.conf('paths.async') + 'stack/add/' + filename,
            type: 'GET',
            success: function (result) {
                // Move all current items one down, and remove the last one.
                var stack = $('#stackholder div.stackitem'),
                    i,
                    ii,
                    item,
                    html;

                for (i = stack.length; i >= 1; i--) {
                    ii = i + 1;
                    item = $('#stackholder div.stackitem.item-' + i);
                    item.addClass('item-' + ii).removeClass('item-' + i);
                }
                if ($('#stackholder div.stackitem.item-8').is('*')) {
                    $('#stackholder div.stackitem.item-8').remove();
                }

                // If added via a button on the page, disable the button, as visual feedback.
                if (element !== null) {
                    $(element).addClass('disabled');
                }

                // Insert new item at the front.
                if (type === 'image') {
                    html = $('#protostack div.image').clone();
                    $(html).find('img').attr('src', bolt.conf('paths.bolt') + '../thumbs/100x100c/' +
                        encodeURI(filename));
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
    };

    /**
     * Select file in modal file selector dialog.
     *
     * @static
     * @function selectFromPulldown
     * @memberof Bolt.stack
     *
     * @param {string} key - Id of the file selector
     * @param {string} path - Path to the selected file
     */
    stack.selectFromPulldown = function (key, path) {
        // For "normal" file and image fields.
        if ($('#field-' + key).is('*')) {
            $('#field-' + key).val(path);
        }

        // For Imagelist fields. Check if bolt.imagelist[key] is an object.
        if (typeof bolt.imagelist === 'object' && typeof bolt.imagelist[key] === 'object') {
            bolt.imagelist[key].add(path, path);
        }

        // For Filelist fields. Check if filelist[key] is an object.
        if (typeof bolt.filelist === 'object' && typeof bolt.filelist[key] === 'object') {
            bolt.filelist[key].add(path, path);
        }

        // If the field has a thumbnail, set it.
        if ($('#thumbnail-' + key).is('*')) {
            var src = bolt.conf('paths.bolt') + '../thumbs/200x150c/' + encodeURI(path);
            $('#thumbnail-' + key).html('<img src="' + src + '" width="200" height="150">');
        }

        // Close the modal dialog, if this image/file was selected through one.
        if ($('#selectModal-' + key).is('*')) {
            $('#selectModal-' + key).modal('hide');
        }

        // If we need to place it on the stack as well, do so.
        if (key === 'stack') {
            bolt.stack.addToStack(path);
        }

        // Make sure the dropdown menu is closed. (Using the "blunt axe" method)
        $('.in, .open').removeClass('in open');
    };

    /**
     * Changes folder in modal file selector dialog.
     *
     * @static
     * @function changeFolder
     * @memberof Bolt.stack
     *
     * @param {string} key - Id of the file selector
     * @param {string} folderUrl - The URL command string to change the folder
     */
    stack.changeFolder = function (key, folderUrl) {
        $('#selectModal-' + key + ' .modal-content').load(folderUrl, function() {
            bolt.actions.init();
        });
    };

    // Apply mixin container
    bolt.stack = stack;

})(Bolt || {}, jQuery);
