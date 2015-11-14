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

            self.element.on('click', '.add-button', function () {
                var template = self.element.find('script[type="text/template"]').html(),
                    slot = self.element.find('.repeater-slot'),
                    newSet = self._clone($(template));

                slot.append(newSet);
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

            $('[data-bolt-field]', cloned).each(function () {
                var field = $(this);

                // Replace all id's and correspondending for-attributes.
                $(field)
                    .attr('id', bolt.app.buid())
                    .find('[id]').each(function () {
                        var id = $(this).attr('id'),
                            nid = bolt.app.buid();

                        $(this).attr('id', nid);

                        $(field).find('[for="' + id + '"]').each(function () {
                            $(this).attr('for', nid);
                        });
                    });
            });
            bolt.app.initWidgets(cloned);

            return cloned;
        }
    });
})(jQuery, Bolt);
