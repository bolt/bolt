/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * BUIC stack widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicStack
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicStack', /** @lends jQuery.widget.bolt.buicStack.prototype */ {
        /**
         * The constructor of the stack widget.
         *
         * @private
         */
        _create: function () {
            bolt.uploads.bindStack(this.element);
        },

        /**
         * Add a file to the stack.
         *
         * @param {string} path - Path to add to the stack
         */
        add: function (path) {
            bolt.stack.addToStack(path);
        },

        /**
         * Add a file item to the stack display.
         *
         * @param {string} path - Path to add to the stack
         */
        prepend: function (path) {
            var stack = $('#stackholder div.stackitem'),
                i,
                ii,
                item,
                html,
                ext = path.substr(path.lastIndexOf('.') + 1).toLowerCase(),
                type;

            if (ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif') {
                type = 'image';
            } else {
                type = 'other';
            }

            // Move all current items one down, and remove the last one.
            for (i = stack.length; i >= 1; i--) {
                ii = i + 1;
                item = $('#stackholder div.stackitem.item-' + i);
                item.addClass('item-' + ii).removeClass('item-' + i);
            }
            if ($('#stackholder div.stackitem.item-8').is('*')) {
                $('#stackholder div.stackitem.item-8').remove();
            }

            // Insert new item at the front.
            if (type === 'image') {
                html = $('#protostack div.image').clone();
                $(html).find('img').attr('src', bolt.conf('paths.bolt') + '../thumbs/100x100c/' +
                    encodeURI(path));
            } else {
                html = $('#protostack div.other').clone();
                $(html).find('strong').html(ext.toUpperCase());
                $(html).find('small').html(path);
            }
            $('#stackholder').prepend(html);

            // If the "empty stack" notice was showing, remove it.
            $('.nostackitems').remove();
        }
    });
})(jQuery, Bolt);
