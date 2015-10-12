/**
 * Handling of BUIC listings.
 *
 * @mixin
 * @namespace Bolt.buic.listing
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.buic.listing mixin container.
     *
     * @private
     * @type {Object}
     */
    var listing = {};

    /**
     * Bind BUIC listings.
     *
     * @static
     * @function init
     * @memberof Bolt.buic.listing
     *
     * @param {Object} buic
     */
    listing.init = function (buic) {
        // Select all rows in a listing section.
        $(buic).find('tr.header th.menu li.select-all a').on('click', function () {
            $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                this.checked = true;
                rowSelection(this);
            });
            handleSelectionState(this);
        });

        // Unselect all rows in a listing section.
        $(buic).find('tr.header th.menu li.select-none a').on('click', function () {
            $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                this.checked = false;
                rowSelection(this);
            });
            handleSelectionState(this);
        });

        // On check/unchecking a row selector.
        $(buic).find('td input:checkbox[name="checkRow"]').on('click', function () {
            rowSelection(this);
            handleSelectionState(this);
        });

        // Record delete action.
        $(buic).find('tr.selectiontoolbar button.records-delete').on('click', function () {
            var tbody = $(this).closest('tbody'),
                table = $(this).closest('table'),
                contenttype = $(table).data('contenttype'),
                checkboxes = tbody.find('td input:checkbox[name="checkRow"]:checked'),
                selectedIds = [],
                selectedRows = [],
                modifications = {},
                notice;

            $(checkboxes).each(function () {
                var row = $(this).parents('tr'),
                    id = row.attr('id').substr(5);

                if (id) {
                    selectedIds.push(id);
                    selectedRows.push(row);
                }
            });

            if (selectedIds.length > 0) {
                // Build POST data.
                modifications[contenttype] = {};
                $(selectedIds).each(function () {
                    modifications[contenttype][this] = {'delete': null};
                    //modifications[contenttype][this] = {'modify': {'status': 'published'}};
                });

                notice = selectedIds.length === 1 ? Bolt.data('recordlisting.delete_one')
                                                  : Bolt.data('recordlisting.delete_mult');

                bootbox.confirm(notice, function (confirmed) {
                    $('.alert').alert();
                    if (confirmed === true) {
                        var url = Bolt.conf('paths.async') + 'content/modify';

                        // Delete request.
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                'path': window.location.pathname + window.location.search + window.location.hash,
                                'pathname': window.location.pathname,
                                'search': window.location.search,
                                'hash': window.location.hash,
                                'bolt_csrf_token': $(table).data('bolt_csrf_token'),
                                'modifications': modifications
                            },
                            success: function (data) {
                                console.log('Success');
                                console.log(data);
                                /*$(selectedRows).each(function () {
                                    $(this).remove();
                                });
                                handleSelectionState(tbody);*/
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.log(jqXHR.status + ' (' + errorThrown + '):');
                                console.log(JSON.parse(jqXHR.responseText));
                            }/*,
                            dataType: 'json'*/
                        });
                    }
                });
            }
        });
    };

    /**
     * Handle row selection.
     *
     * @private
     * @static
     * @function rowSelection
     * @memberof Bolt.files
     *
     * @param {object} checkbox - Checkbox clicked.
     */
    function rowSelection(checkbox) {
        var row = $(checkbox).closest('tr');

        if (checkbox.checked) {
            row.addClass('row-selected');
        } else {
            row.removeClass('row-selected');
        }
    }

    /**
     * Hide/Show selection toolbar.
     *
     * @private
     * @static
     * @function handleSelectionState
     * @memberof Bolt.files
     *
     * @param {object} element - Element inside a tbody.
     */
    function handleSelectionState(element) {
        var tbody = $(element).closest('tbody'),
            menu = tbody.find('tr.header th.menu'),
            menuSel = menu.find('li.dropdown-header'),
            toolbar = tbody.find('tr.selectiontoolbar'),
            count = tbody.find('td input:checkbox[name="checkRow"]:checked').length,
            menuitems = menu.find('li.on-selection');

        // Show/hide toolbar & menu entries.
        if (count) {
            toolbar.removeClass('hidden');
            menuitems.removeClass('hidden');
        } else {
            toolbar.addClass('hidden');
            menuitems.addClass('hidden');
        }
        // Update selection count display.
        toolbar.find('div.count').text(count);
        // Update menu.
        menuSel.text(menuSel.text().replace(/\([#0-9]+\)/, '(' + count + ')'));
    }

    // Apply mixin container
    bolt.buic.listing = listing;

})(Bolt || {}, jQuery);
