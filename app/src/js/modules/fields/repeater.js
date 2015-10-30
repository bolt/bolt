/**
 * Handling for repeating fields.
 *
 * @mixin
 * @namespace Bolt.fields.repeater
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.repeater mixin container.
     *
     * @private
     * @type {Object}
     */
    var repeater = {};

    /**
     * Bind repeater field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.repeater
     *
     * @param {Object} fieldset
     */
    repeater.init = function (fieldset) {
        $(fieldset).on('click', '.add-button', function () {
            var template = $(fieldset).find('script[type="text/template"]').html(),
                slot = $(fieldset).find('.repeater-slot'),
                newSet = clone($(template));

            slot.append(newSet);
            bolt.buic.init(newSet);
        });

        $(fieldset).on('click', '.duplicate-button', function () {
            var setToDuplicate = $(this).closest('.repeater-group'),
                duplicatedSet = clone(setToDuplicate);

            setToDuplicate.after(duplicatedSet);
            bolt.buic.init(duplicatedSet);
        });

        $(fieldset).on('click', '.delete-button', function () {
            var setToDelete = $(this).closest('.repeater-group');

            setToDelete.remove();
        });
    };

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
    function clone(template) {
        var cloned = $(template).clone();

        $('[data-bolt-field]', cloned).each(function () {
            var field = $(this),
                type = $(field).data('bolt-field'),
                conf = $(field).data('bolt-fconf');

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

            // Implemented fields:
            if (typeof bolt.fields[type] !== 'undefined' && typeof bolt.fields[type].init === 'function') {
                bolt.fields[type].init(this, conf);
            }
        });

        return cloned;
    }

    // Apply mixin container
    bolt.fields.repeater = repeater;

})(Bolt || {}, jQuery);
