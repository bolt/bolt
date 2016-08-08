/**
 * Helper to add commands directly to html tags.
 *
 * @example
 *      &lt;button data-action="eventView.load()"&gt;
 *
 * @mixin
 * @namespace Bolt.actions
 * @deprecated Uses ``eval()`` which makes it a candidate for a cleaner replacement.
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /*
     * Bolt.actions mixin container.
     */
    var actions = {};

    /**
     * Bind action executing to tags with ``data-actions`` attribute.
     *
     * @static
     * @function init
     * @memberof Bolt.actions
     */
    actions.init = function () {
        // Unbind the click events, with the 'action' namespace.
        $('button, input[type=button], a').off('click.action');

        $('[data-action]')
            // Bind the click events, with the 'action' namespace.
            .on('click.action', function (e) {
                var action = $(this).attr('data-action');

                if (typeof action !== 'undefined' && action !== '') {
                    e.preventDefault();
                    eval(action); // eslint-disable-line no-eval
                    e.stopPropagation();
                }
            })
            // Prevent propagation to parent's click handler from anchor in popover.
            .on('click.popover', '.popover', function (e) {
                e.stopPropagation();
            });

        // Add 'spinners' for buttons that should be clicked only once.
        $('.clickspinner').on('click.spinner', function () {
            $(this).addClass('disabled').blur();
            $(this).find('i').addClass('fa-spin fa-spinner');

            // Timeout of 10 seconds. The action really should have finished by now. Otherwise,
            // just let the user try again.
            window.setTimeout(function (self){
                $(self).removeClass('disabled');
                $(self).find('i').removeClass('fa-spin fa-spinner');
            }, 10000, this);
        });

        // Add 'spinners' for forms that should be submitted only once.
        $('.submitspinner').on('submit.spinner', function () {
            $(this).find('button[type=submit]').addClass('disabled').blur();
            $(this).find('button[type=submit] i').addClass('fa-spin fa-spinner');

            // Timeout of 10 seconds. The action really should have finished by now. Otherwise,
            // just let the user try again.
            window.setTimeout(function (self){
                $(self).removeClass('disabled');
                $(self).find('i').removeClass('fa-spin fa-spinner');
            }, 10000, $(this).find('button[type=submit]'));
        });

    };

    // Apply mixin container
    bolt.actions = actions;

})(Bolt || {}, jQuery);
