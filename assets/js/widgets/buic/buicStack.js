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
             */
            this._ui = {
                holder: fieldset.find('.stackholder')
            };

            // Listen to external events.
            self._on({
                'buicbrowserselected': self._addPath,
                'buicuploaduploaded':  self._addPath
            });

            // Initialize moment timestamps when popover content is added to DOM
            fieldset.on('inserted.bs.popover', '.stackitem', function () {
                bolt.app.initWidgets();
            });

            fieldset.buicUpload();
        },

        /**
         * Add a file item to the stack display.
         *
         * @param {string} stackItem - HTML of stack item.
         * @param {string|null} removed - The removed file path, if any.
         */
        prepend: function (stackItem, removed) {
            if (removed) {
                this._ui.holder.find('[data-file]').each(function () {
                    if ($(this).data('file').fullPath === removed) {
                        $(this).remove();
                    }
                });
            }

            // If the "empty stack" notice was showing, remove it.
            this._ui.holder.find('.empty').remove();

            // Add new item
            this._ui.holder.prepend(stackItem);

            // Initialize popover for new item
            // Settings copied from init.popOvers. DRY up somehow?
            this._ui.holder.find('.info-pop').popover({
                trigger: 'hover',
                delay: {
                    show: 500,
                    hide: 200
                }
            });
        }
    });
})(jQuery, Bolt);
