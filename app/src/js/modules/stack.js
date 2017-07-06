/* eslint no-console: ["error", { allow: ["error"] }] */
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
        $.post(bolt.conf('stackAddUrl'), {
            filename: filename,
        })
            .done(function (data) {
                // If added via a button on the page, disable the button, as visual feedback.
                if (element) {
                    $(element).addClass('disabled');
                }

                $(':bolt-buicStack').buicStack('prepend', data.panel, data.removed);

                // Move to better spot? rarila?
                if (data.removed) {
                    $('.select-from-stack [data-file]').each(function () {
                        if ($(this).data('file').fullPath === data.removed) {
                            $(this).remove();
                        }
                    });
                }
                // Prepend item to stacks (if type filter exists, only if it matches)
                $('.select-from-stack').each(function () {
                    var type = $(this).data('type');
                    if (!type || data.type === type) {
                        $(this).prepend(data.list);
                    }
                });
            })
            .fail(function () {
                console.error('Failed to add file to stack');
            });
    };

    // Apply mixin container
    bolt.stack = stack;

})(Bolt || {}, jQuery);
