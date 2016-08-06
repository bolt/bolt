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
         * Add a file to the stack.
         *
         * @private
         *
         * @param {Object}                                             event - The event
         * @param {jQuery.widget.bolt.buicBrowser#buicbrowserselected|
         *         jQuery.widget.bolt.buicUpload#buicuploaduploaded}   data  - Data containing the path
         */
        _addPath: function (event, data) {
            bolt.stack.addToStack(data.path);
        },

        /**
         * The constructor of the stack widget.
         *
         * @private
         * @listens jQuery.widget.bolt.buicBrowser#buicbrowserselected
         * @listens jQuery.widget.bolt.buicUpload#buicuploaduploaded
         */
        _create: function () {
            var self = this,
                fieldset = this.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.buicStack.prototype
             * @private
             *
             * @property {Object} holder         - Stackholder
             * @property {Object} template       - Templates
             * @property {Object} template.image - Template for stackitems of type 'image'
             * @property {Object} template.other - Template for stackitems of type 'other'
             */
            this._ui = {
                holder: fieldset.find('.stackholder'),
                template: {
                    image: fieldset.find('.templates .image'),
                    other: fieldset.find('.templates .other')
                }
            };

            // Listen to external events.
            self._on({
                'buicbrowserselected': self._addPath,
                'buicuploaduploaded':  self._addPath
            });

            fieldset.buicUpload();
        },

        /**
         * Add a file item to the stack display.
         *
         * @param {string} path - Path to add to the stack
         */
        prepend: function (path) {
            var ext = path.substr(path.lastIndexOf('.') + 1).toUpperCase();

            // Remove the last stackitem.
            $('.stackitem:nth-child(7)', this._ui.holder).remove();

            // If the "empty stack" notice was showing, remove it.
            $('.empty').remove();

            // Insert new item at the front.
            this._ui.template[ext.match(/^(JPE?G|PNG|GIF)$/) ? 'image' : 'other'].clone()
                .find('img').attr('src', bolt.conf('paths.bolt') + '../thumbs/100x100c/' + encodeURI(path)).end()
                .find('strong').html(ext).end()
                .find('small').html(path).end()
                .prependTo(this._ui.holder);
        }
    });
})(jQuery, Bolt);
