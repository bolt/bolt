/**
 * Helper to get all selected Items and return Array
 */
function getSelectedItems(){
    var aItems = [];
    $('.dashboardlisting input:checked').each(function(index) {
        if ($(this).parents('tr').attr('id')) {
            aItems.push($(this).parents('tr').attr('id').substr(5));
        }
    });
    console.log('getSelectedItems: ' + aItems);
    return aItems;
}

/**
 * Helper to make things like '<button data-action="eventView.load()">' work
 */
function initActions() {
    // Unbind the click events, with the 'action' namespace.
    $('button, input[type=button], a').off('click.action');

    // Bind the click events, with the 'action' namespace.
    $('[data-action]').on('click.action', function(e) {
        var action = $(this).data('action');
        if (typeof action !== "undefined" && action !== "") {
            eval(action);
            e.stopPropagation();
            e.preventDefault();
        }
    })
    // Prevent propagation to parent's click handler from anchor in popover.
    .on('click.popover', '.popover', function(e) {
        e.stopPropagation();
    });
}
