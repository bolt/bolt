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
    $.widget('bolt.fieldBlock', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldRepeater.prototype */ {
        /**
         * Default options.
         *
         * @property {string} name    - Prefix for field names
         */
        options: {
            name:  ''
        },

        /**
         * The constructor of the repeater field widget.
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
             * @property {Object} add  - Add button
             * @property {Object} slot - Group container
             */
            this._ui = {
                add:  self.element.find('.add-button'),
                slot: self.element.find('.block-slot')
            };

            /**
             * The repeater template.
             *
             * @type {Object}
             * @name _template
             * @memberOf jQuery.widget.bolt.fieldRepeater.prototype
             * @private
             */
            this._template = $(self.element.find('script[type="text/template"]').html());

            // Adjust upper limit.
            if (self.options.maximum === 0) {
                self.options.maximum = Infinity;
            }
            self._setCount();

            self._ui.add.on('click', function () {
                self._append();
            });
        },

        /**
         * Appends a new empty group.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.block
         */
        _append: function () {
            var newSet = this._clone(this._template);

            this._ui.slot.append(newSet);
            this._setCount(1);
            this._renumber();
            bolt.datetime.init();
            bolt.ckeditor.init();
            init.popOvers();
        }

    });
})(jQuery, Bolt, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
