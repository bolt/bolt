/*
 * Bolt module: Actions
 *
 * Helper to make things like '<button data-action="eventView.load()">' work.
 *
 * @type {function}
 * @mixin
 */
var BoltActions = (function (bolt, $) {
    /*
     * BoltDataActions mixin
     */
    bolt.actions = {};

    /*
     * Read configuration data from DOM and save it in module
     */
    bolt.actions.bind = function () {
        // Unbind the click events, with the 'action' namespace.
        $('button, input[type=button], a').off('click.action');

        // Bind the click events, with the 'action' namespace.
        $('[data-action]').on('click.action', function (e) {
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

    return bolt;
})(Bolt || {}, jQuery);
