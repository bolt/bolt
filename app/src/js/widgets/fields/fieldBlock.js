/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Block field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rossriley
     *
     * @class fieldBlock
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldBlock', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldBlock.prototype */ {
        /**
         * Default options.
         *
         * @property {string} name    - Prefix for field names
         */
        options: {
            name:  ''
        },

        /**
         * The constructor of the block field widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldBlock.prototype
             * @private
             *
             * @property {Object} add  - Add buttons
             * @property {Object} slot - Group container
             */
            this._ui = {
                add:  self.element.find('.block-add .add-button'),
                slot: self.element.find('.block-slot')
            };

            /**
             * The block templates.
             *
             * @type {Object}
             * @name _templates
             * @memberOf jQuery.widget.bolt.fieldBlock.prototype
             * @private
             */
            this._templates = $(self.element.find('script[type="text/template"]').html());
            

            self._ui.add.on('click', function (el) {
                self._append(el);
            });
        },

        /**
         * Appends a new empty group.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.block
         */
        _append: function (el) {
            console.log(el);

            bolt.datetime.init();
            bolt.ckeditor.init();
            init.popOvers();
        }

    });
})(jQuery, Bolt, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
