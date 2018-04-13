/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 * @param {Object} cke  - CKEDITOR global or undefined
 */
(function ($, bolt, cke) {
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

            self.element.on('click', '.delete-button', function () {
                var setToDelete = $(this).closest('.block-group');

                setToDelete.remove();
                self._renumber();
            });

            self.element.on('click', '.move-up', function () {
                var setToMove = $(this).closest('.block-group');

                setToMove.insertBefore(setToMove.prev('.block-group'));
                self._renumber();
                self._resetEditors(setToMove);
            });

            self.element.on('click', '.move-down', function () {
                var setToMove = $(this).closest('.block-group');

                setToMove.insertAfter(setToMove.next('.block-group'));
                self._renumber();
                self._resetEditors(setToMove);
            });

            self.element.on('click', '.toggle-block', function () {
                var setToToggle = $(this).closest('.block-group').find('.panel-body');

                $(this).toggleClass('fa-chevron-circle-down').toggleClass('fa-chevron-circle-right');
                setToToggle.slideToggle();
            });
            
            self.element.on('click', '.hide-all-blocks', function () {
                var $container = $(this).closest('.bolt-field-block');
                var setToHide = $container.find('.panel-body');
                var triggerToChange = $container.find('.toggle-block');

                triggerToChange.removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-right');
                setToHide.slideUp();
            });

            self.element.on('click', '.show-all-blocks', function () {
                var $container = $(this).closest('.bolt-field-block');
                var setToShow = $container.find('.panel-body');
                var triggerToChange = $container.find('.toggle-block');

                triggerToChange.removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-down');
                setToShow.slideDown();
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

            $.each(self._templates, function (index, templateItem) {
                if ($(templateItem).data('block-type') === templateType) {
                    newTemplate = $(templateItem).html();
                }
            });
            var newSet = this._clone(newTemplate);
            newSet.data('block-type', templateType);
            this._ui.slot.append(newSet);
            this._renumber();

            bolt.datetime.init();
            bolt.ckeditor.init();
            init.popOvers();
        },

        /**
         * Clones a template or a block and initializes it.
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

        /**
         * Renumbers group input names.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.block
         */
        _renumber: function () {
            var re = new RegExp('^([^\\\[]+\\\[)([#|\\\d]+)(\\\].*)$', 'gi');

            this._ui.slot.find('div.block-group').each(function (index, group) {
                $(group).find('[name]').each(function () {
                    this.name = this.name.replace(re, '$1' + index + '$3');
                });

                if ($(group).is(':first-of-type')) {
                    $(group).find('.move-up').addClass('disabled');
                } else {
                    $(group).find('.move-up').removeClass('disabled');
                }

                if ($(group).is(':last-of-type')) {
                    $(group).find('.move-down').addClass('disabled');
                } else {
                    $(group).find('.move-down').removeClass('disabled');
                }
            });
        },

        /**
         * Reset ckeditors within a given context.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.block
         *
         * @param {Object} container - jQuery context object
         */
        _resetEditors: function (container) {
            var editors = container.find('.ckeditor');

            editors.each(function (i, editor) {
                if (cke.instances[editor.id]) {
                    cke.instances[editor.id].destroy();
                }
                cke.replace(editor.id);
            });
        },

    });
})(jQuery, Bolt, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
