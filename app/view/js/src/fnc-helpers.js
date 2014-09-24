/**
 * Helper to get all selected Items and return Array
 */
function getSelectedItems() {
    var aItems = [];
    $('.dashboardlisting input:checked').each(function (index) {
        if ($(this).parents('tr').attr('id')) {
            aItems.push($(this).parents('tr').attr('id').substr(5));
        }
    });
    console.log('getSelectedItems: ' + aItems);
    return aItems;
}
