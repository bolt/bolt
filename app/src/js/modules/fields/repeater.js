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
            template = $(fieldset).find('script[type="text/template"]'),
            slot = $(fieldset).find('.repeater-slot');

        addButton.on('click', function (evt){
            var newSet = $(template.html());

            slot.append(newSet);
            bolt.fields.init(newSet);
            bolt.buic.init(newSet);
            e.preventDefault();
        });

        $(fieldset).on('click', '.duplicate-button', function (evt){
            var setToDuplicate = $(this).closest('.repeater-group'),
                duplicatedSet = $(setToDuplicate[0].outerHTML);

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

    // Apply mixin container
    bolt.fields.repeater = repeater;

})(Bolt || {}, jQuery);
