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
        var addButton = $(fieldset).find('.repeater-add a');
        var template = $(fieldset).find("script[type='text/template']");
        var slot = $(fieldset).find('.repeater-slot');

        addButton.on('click', function(e){
           slot.append(template.html());
            e.preventDefault();
        });

        $(fieldset).on('click', '.duplicate-button', function(){
            var setToDuplicate = $(this).closest('repeater-group');
            setToDuplicate.after(setToDuplicate.html());
        });

        $(fieldset).on('click', '.delete-button', function(){
            var setToDelete = $(this).closest('repeater-group');
            setToDelete.remove();
        });
    };

    // Apply mixin container
    bolt.fields.repeater = repeater;

})(Bolt || {}, jQuery);
