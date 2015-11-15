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
             * @property {Object} edit   - Edit button.
             * @property {Object} copy   - Copy button.
             */
            this._ui = {
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

            self.element.on('click', '.add-button', function () {
                var newSet = self._clone(self._template);

                self._ui.slot.append(newSet);
            });

            self.element.on('click', '.duplicate-button', function () {
                var setToDuplicate = $(this).closest('.repeater-group'),
                    duplicatedSet = self._clone(setToDuplicate);

                setToDuplicate.after(duplicatedSet);
            });

            self.element.on('click', '.delete-button', function () {
                var setToDelete = $(this).closest('.repeater-group');

                setToDelete.remove();
            });
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
        }
    });
})(jQuery, Bolt);
