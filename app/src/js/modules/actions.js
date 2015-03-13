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
                    eval(action); // jshint ignore:line
                    e.stopPropagation();
                }
            })
            // Prevent propagation to parent's click handler from anchor in popover.
            .on('click.popover', '.popover', function (e) {
                e.stopPropagation();
            });
    };

    // Apply mixin container
    bolt.actions = actions;

})(Bolt || {}, jQuery);
