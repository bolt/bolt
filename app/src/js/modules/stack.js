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
    'use strict';

    /**
     * Bolt.stack mixin container.
     *
     * @private
     * @type {Object}
     */
    var stack = {};

    /**
     * Remember last opened url by key.
     *
     * @private
     * @type {Object}
     */
    var history = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.stack
     */
    stack.init = function () {
        bolt.uploads.bindStack($('div.stack-buttons'));

        // Initialze file browser modal.
        $('#modal-server-select').on(
            'show.bs.modal',
            function (event) {
                browserLoad($(event.relatedTarget).data('modal-source'));
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
            success: function () {
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
     * @function select
     * @memberof Bolt.stack
     *
     * @param {string} fieldid - Id of the fieldset
     * @param {string} path - Path to the selected file
     */
    stack.select = function (fieldid, path) {
        var container = $('#' + fieldid);

        switch (container.data('bolt-field')) {
            case 'file':
            case 'image':
                $('input.path', container).val(path).trigger('change');
                break;
            case 'filelist':
                bolt.uploads.addToList(container, path, path);
                break;
            case 'imagelist':
                bolt.uploads.addToList(container, path, path);
                break;
            default:
                bolt.stack.addToStack(path);
        }

        // Close the modal dialog, if this image/file was selected through one.
        $('#modal-server-select').modal('hide');

        // Make sure the dropdown menu is closed. (Using the "blunt axe" method)
        $('.in, .open').removeClass('in open');
    };

    /**
     * Changes folder in modal file selector dialog.
     *
     * @private
     * @function browserLoad
     * @memberof Bolt.stack
     *
     * @param {string} url - The URL to load into the file browser window.
     * @param {boolean} change - Reload on "change folder".
     */
    function browserLoad(url, change) {
        var fieldId = url.match(/\?fieldid=(.+?)$/)[1];

        if (change || !history[fieldId]) {
            history[fieldId] = url;
        }

        $('#modal-server-select .modal-dialog').load(history[fieldId] + ' .modal-content', function (response, status) {
            if (status === 'success' || status === 'notmodified') {
                $('#modal-server-select')
                    // Init change folder action.
                    .find('[data-fbrowser-chdir]').on('click', function (evt) {
                        evt.preventDefault();
                        browserLoad($(this).data('fbrowser-chdir'), true);
                    })
                    .end()
                    // Init file select action.
                    .find('[data-fbrowser-select]').on('click', function (evt) {
                        evt.preventDefault();
                        stack.select(
                            $(this).closest('[data-fbrowser-fieldid]').data('fbrowser-fieldid'),
                            $(this).data('fbrowser-select')
                        );
                    })
                    // Show dialog.
                    .show();
            }
        });
    }

    // Apply mixin container
    bolt.stack = stack;

})(Bolt || {}, jQuery);
