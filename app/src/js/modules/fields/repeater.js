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
        var addButton = $(fieldset).find('.repeater-add a'),
            slot = $(fieldset).find('.repeater-slot');

        addButton.on('click', function (evt){
            var template = $(fieldset).find('script[type="text/template"]').html(),
                newSet = clone($(template));

            slot.append(newSet);
            bolt.fields.init(newSet);
            bolt.buic.init(newSet);
            e.preventDefault();
        });

        $(fieldset).on('click', '.duplicate-button', function (evt){
            var setToDuplicate = $(this).closest('.repeater-group'),
                duplicatedSet = clone(setToDuplicate);

            setToDuplicate.after(duplicatedSet);
            bolt.fields.init(duplicatedSet);
            bolt.buic.init(duplicatedSet);
            e.preventDefault();
        });

        $(fieldset).on('click', '.delete-button', function(){
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

        return cloned;
    }

    // Apply mixin container
    bolt.fields.repeater = repeater;

})(Bolt || {}, jQuery);
