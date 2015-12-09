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
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.stack
     */
    stack.init = function () {
        // Initialize add-to-stack button.
        $('a[data-bolt-addtostack]').each(function () {
            $(this).on('click', function (event) {
                var button = $(event.currentTarget),
                    file = button.data('bolt-addtostack');

                event.preventDefault();
                stack.addToStack(file, button);
            });
        });
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

        $.get(bolt.conf('paths.async') + 'stack/add/' + filename)
            .done(function () {
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
            })
            .fail(function () {
                console.log('Failed to add file to stack');
            });
    };

    // Apply mixin container
    bolt.stack = stack;

})(Bolt || {}, jQuery);
