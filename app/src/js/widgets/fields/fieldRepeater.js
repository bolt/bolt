/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 * @param {Object} cke  - CKEDITOR global or undefined
 */
(function ($, bolt, cke) {
    'use strict';

    /**
     * Repeater field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldRepeater
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldRepeater', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldRepeater.prototype */ {
        /**
         * Default options.
         *
         * @property {number} minimum - Minimum number of groups
         * @property {number} maximum - Maximum number of groups, 0 means unlimited
         * @property {string} name    - Prefix for field names
         */
        options: {
            minimum: 1,
            maximum: 1,
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
             * @memberOf jQuery.widget.bolt.fieldRepeater.prototype
             * @private
             *
             * @property {Object} add  - Add button
             * @property {Object} slot - Group container
             */
            this._ui = {
                add:  self.element.find('.add-button'),
                slot: self.element.find('.repeater-slot')
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

            /**
             * The repeater template.
             *
             * @type {Object}
             * @name _template
             * @memberOf jQuery.widget.bolt.fieldRepeater.prototype
             * @private
             */
            this._count = self._ui.slot.find('div.repeater-group').length;

            // Adjust upper limit.
            if (self.options.maximum === 0) {
                self.options.maximum = Infinity;
            }
            self._setCount();

            self._ui.add.on('click', function () {
                self._append();
            });

            self.element.on('click', '.duplicate-button', function () {
                var setToDuplicate = $(this).closest('.repeater-group'),
                    //duplicatedSet = self._clone(setToDuplicate),
                    newSet = self._clone(self._template);

                // Copy values from source to new set.
                $(setToDuplicate).find(":input").each(function (index) {
                    var val = $(this).val();
                    if (val) {
                        $(newSet).find(':input').eq(index).val(val);
                    }
                });

                setToDuplicate.after(newSet);
                self._setCount(1);
                self._renumber();
            });

            self.element.on('click', '.delete-button', function () {
                var setToDelete = $(this).closest('.repeater-group');

                setToDelete.remove();
                self._setCount(-1);
                self._renumber();
            });

            self.element.on('click', '.move-up', function () {
                var setToMove = $(this).closest('.repeater-group');

                setToMove.insertBefore(setToMove.prev('.repeater-group'));
                self._renumber();
                self._resetEditors(setToMove);
            });

            self.element.on('click', '.move-down', function () {
                var setToMove = $(this).closest('.repeater-group');

                setToMove.insertAfter(setToMove.next('.repeater-group'));
                self._renumber();
                self._resetEditors(setToMove);
            });

            // Add initial groups until minimum number is reached.
            while (self._count < self.options.minimum) {
                self._append();
            }
        },

        /**
         * Appends a new empty group.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.repeater
         */
        _append: function () {
            var newSet = this._clone(this._template);

            this._ui.slot.append(newSet);
            this._setCount(1);
            this._renumber();
            bolt.datetime.init();
            bolt.ckeditor.init();
            init.popOvers();
        },

        /**
         * Clones a template or a repeater and initializes it.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.repeater
         *
         * @param {Object} template
         * @return {Object}
         */
        _clone: function (template) {
            var cloned = $(template).clone();

            // Replace all id's and correspondending for-attributes.
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
         * @memberof Bolt.fields.repeater
         */
        _renumber: function () {
            var name = this.options.name,
                nameEsc = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'),
                re = new RegExp('^' + nameEsc + '\\\[(#|\\\d+)\\\]');

            //console.log('_renumber');
            this._ui.slot.find('div.repeater-group').each(function (index, group) {
                //console.log('  Group ' + index + ':');

                $(group).find('[name]').each(function () {
                    this.name = this.name.replace(re, name + '[' + index + ']');
                    //console.log('  - ' + this.name + ' => ' +this.name.replace(re, name + '[' + index + ']'));
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
         * @memberof Bolt.fields.repeater
         *
         * @param {Object} container - jQuery context object
         */
        _resetEditors: function (container) {
            var editors = container.find('.ckeditor');

            editors.each(function (i, editor) {
                cke.instances[editor.id].destroy();
                cke.replace(editor.id);
            });
        },

        /**
         * Adds a value to the group counter and adjust button states according to it.
         *
         * @private
         * @function clone
         * @memberof Bolt.fields.repeater
         * @param {number} [add=0] - The value to add to the counter
         */
        _setCount: function (add) {
            this._count += add || 0;

            if (this._count >= this.options.maximum) {
                this._ui.add.addClass('disabled');
                this.element.find('.duplicate-button').addClass('disabled');
            } else {
                this._ui.add.removeClass('disabled');
                this.element.find('.duplicate-button').removeClass('disabled');
            }

            if (this._count <= this.options.minimum) {
                this.element.find('.delete-button').addClass('disabled');
            } else {
                this.element.find('.delete-button').removeClass('disabled');
            }
        }
    });
})(jQuery, Bolt, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
