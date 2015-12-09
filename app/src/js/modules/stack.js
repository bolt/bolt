/**
 * Stack-related functionality.
 *
 * @mixin
 * @namespace Bolt.stack
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.stack mixin container.
     *
     * @private
     * @type {Object}
     */
    var stack = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.stack
     */
    stack.init = function () {
        // Initialize add-to-stack button.
        $('a[data-bolt-addtostack]').each(function () {
            $(this).on('click', function (event) {
                var button = $(event.currentTarget),
                    file = button.data('bolt-addtostack');

                event.preventDefault();
                stack.addToStack(file, button);
            });
        });
    };

    /**
     * Add a file to the stack.
     *
     * @static
     * @function addToStack
     * @memberof Bolt.stack
     *
     * @param {string} filename - The name of the file to add
     * @param {object} element - The object that calls this function
     */
    stack.addToStack = function (filename, element) {
        // We don't need 'files/' in the path. Accept input with or without it, but strip it out here.
        filename = filename.replace(/files\//ig, '');

        $.get(bolt.conf('paths.async') + 'stack/add/' + filename)
            .done(function () {
                // If added via a button on the page, disable the button, as visual feedback.
                if (element) {
                    $(element).addClass('disabled');
                }

                $(':bolt-buicStack').buicStack('prepend', filename);
            })
            .fail(function () {
                console.log('Failed to add file to stack');
            });
    };

    // Apply mixin container
    bolt.stack = stack;

})(Bolt || {}, jQuery);
