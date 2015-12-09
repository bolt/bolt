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
            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.buicStack.prototype
             * @private
             *
             * @property {Object} holder         - Stackholder
             * @property {Object} templateImage  - Template for stackitems of type 'image'
             * @property {Object} templateOther  - Template for stackitems of type 'other'
             */
            this._ui = {
                holder:        this.element.find('.stackholder'),
                templateImage: this.element.find('.templates .image'),
                templateOther: this.element.find('.templates .other')
            };

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
            var ext = path.substr(path.lastIndexOf('.') + 1).toLowerCase(),
                html;

            // Remove the last stackitem.
            $('.stackitem:nth-child(7)', this._ui.holder).remove();

            // Insert new item at the front.
            if (ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif') {
                html = this._ui.templateImage.clone();
                $(html).find('img').attr('src', bolt.conf('paths.bolt') + '../thumbs/100x100c/' + encodeURI(path));
            } else {
                html = this._ui.templateOther.clone();
                $(html).find('strong').html(ext.toUpperCase());
                $(html).find('small').html(path);
            }
            this._ui.holder.prepend(html);

            // If the "empty stack" notice was showing, remove it.
            $('.empty').remove();
        }
    });
})(jQuery, Bolt);
