/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Repeater field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldRepeater
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldRepeater', /** @lends jQuery.widget.bolt.fieldRepeater.prototype */ {
        /**
         * Default options.
         *
         * @property {number} limit - Maximum number ouf groups, 0 means unlimited
         * @property {string} name  - Prefix for field names
         */
        options: {
            limit: '',
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

            self._ui.add.on('click', function () {
                self._append();
            });

            self.element.on('click', '.duplicate-button', function () {
                var setToDuplicate = $(this).closest('.repeater-group'),
                    duplicatedSet = self._clone(setToDuplicate);

                setToDuplicate.after(duplicatedSet);
                self._count++;
                self._renumber();
            });

            self.element.on('click', '.delete-button', function () {
                var setToDelete = $(this).closest('.repeater-group');

                setToDelete.remove();
                self._count--;
                self._renumber();
            });

            // Add initial group if there is none.
            if (self._count === 0) {
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
            this._count++;
            this._renumber();
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
                re = new RegExp('^' + nameEsc + '\\\[(#|\\\d)\\\]');

            //console.log('_renumber');
            this._ui.slot.find('div.repeater-group').each(function (index, group) {
                //console.log('  Group ' + index + ':');

                $(group).find('[name]').each(function () {
                    this.name = this.name.replace(re, name + '[' + index + ']');
                    //console.log('  - ' + this.name + ' => ' +this.name.replace(re, name + '[' + index + ']'));
                });
            });
        }
    });
})(jQuery, Bolt);
