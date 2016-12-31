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
            this._templates = self.element.find('script[type="text/template"]');


            self._ui.add.on('click', function (el) {
                self._append(el);
            });
        },

        /**
         * Appends a new empty group.
         *
         * @private
         * @function _append
         * @memberof Bolt.fields.block
         */
        _append: function (el) {
            var self = this;
            var templateType = $(el.target).data('block-type');
            var newTemplate;
            console.log(self._templates);
            $.each(self._templates, function (index, templateItem) {
                console.log(templateItem);
                console.log(templateType);
                if (templateItem.data('block-type') === templateType) {
                    newTemplate = templateItem.html();
                }
            });
            var newSet = this._clone(newTemplate);
            this._ui.slot.append(newSet);

            bolt.datetime.init();
            bolt.ckeditor.init();
            init.popOvers();
        },

        /**
         * Clones a template or a repeater and initializes it.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.block
         *
         * @param {Object} template
         * @return {Object}
         */
        _clone: function (template) {
            var cloned = $(template).clone();

            // Replace all id's and corresponding for-attributes.
            cloned.find('[id]').each(function () {
                var id = $(this).attr('id'),
                    nid = bolt.app.buid();

                $(this).attr('id', nid);

                cloned.find('[for="' + id + '"]').each(function () {
                    $(this).attr('for', nid);
                });
            });

            bolt.app.initWidgets(cloned);

            return cloned;
        },

    });
})(jQuery, Bolt, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
