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
        initEvents(buic);
    };

    /**
     * Initializes all events inside a record listing table.
     *
     * @private
     * @static
     * @function initEvents
     * @memberof Bolt.buic.listing
     *
     * @param {Object} buic - Listing table
     */
    function initEvents(buic) {
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

        // Record toolbar actions.
        $(buic).find('tr.selectiontoolbar button[data-stb-cmd^="record:"]').each(function () {
            $(this).on('click', function () {
                modifyRecords(this, $(this).data('stb-cmd').replace(/^record:/, ''));
            });
        });

        // Record row edit button actions.
        $(buic).find('a[data-listing-cmd^="record:"]').each(function () {
            var id = $(this).parents('tr').attr('id').substr(5);

            $(this).on('click', function () {
                modifyRecords(this, $(this).data('listing-cmd').replace(/^record:/, ''), [id]);
            });
        });
    }

    /**
     * Execute commands on triggered button.
     *
     * @private
     * @static
     * @function modifyRecords
     * @memberof Bolt.buic.listing
     *
     * @param {object} button - Triggered list button.
     * @param {string} action - Triggered action (Allowed: 'delete').
     * @param {array} ids - Optional array of ids to perform the action on.
     */
    function modifyRecords(button, action, ids) {
        var container = $(button).closest('div.record-listing-container'),
            table = $(button).closest('table'),
            tbody = $(button).closest('tbody'),
            contenttype = $(table).data('contenttype'),
            checkboxes = tbody.find('td input:checkbox[name="checkRow"]:checked'),
            selectedIds = [],
            modifications = {},
            actions = {
                'delete': {
                    'name': Bolt.data('recordlisting.action.delete'),
                    'cmd': {'delete': null}
                },
                'publish': {
                    'name': Bolt.data('recordlisting.action.publish'),
                    'cmd': {'modify': {'status': 'published'}}
                },
                'depublish': {
                    'name': Bolt.data('recordlisting.action.depublish'),
                    'cmd': {'modify': {'status': 'held'}}
                },
                'draft': {
                    'name': Bolt.data('recordlisting.action.draft'),
                    'cmd': {'modify': {'status': 'draft'}}
                }
            },
            buttonText = $(button).html(),
            msg;

        if (ids) {
            selectedIds = ids;
        } else {
            $(checkboxes).each(function () {
                var row = $(this).parents('tr'),
                    id = row.attr('id').substr(5);

                if (id) {
                    selectedIds.push(id);
                }
            });
        }

        if (selectedIds.length > 0) {
            // Build POST data.
            modifications[contenttype] = {};
            $(selectedIds).each(function () {
                modifications[contenttype][this] = actions[action].cmd;
            });

            // Build message:
            if (selectedIds.length === 1) {
                msg = Bolt.data('recordlisting.confirm.one');
            } else {
                msg = Bolt.data('recordlisting.confirm.multi', {'%NUMBER%': '<b>' + selectedIds.length + '</b>'});
            }
            msg = msg + '<br><br><b>' + Bolt.data('recordlisting.confirm.no-undo') + '</b>';

            // Remove when done:
            msg = msg + '<hr><b style="color:red;">Anti CSRF token functionality still disabled ' +
                'in Bolt\Controller\Async\Records::modify</b>';

            bootbox.dialog({
                message: msg,
                title: actions[action].name,
                buttons: {
                    cancel: {
                        label: Bolt.data('recordlisting.action.cancel'),
                        className: 'btn-default'
                    },
                    ok: {
                        label: buttonText,
                        className: 'btn-primary',
                        callback: function () {
                            var url = Bolt.conf('paths.async') + 'content/action' + window.location.search;

                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: {
                                    'bolt_csrf_token': $(table).data('bolt_csrf_token'),
                                    'contenttype': contenttype,
                                    'actions': modifications
                                },
                                success: function (data) {
                                    var table;

                                    $(container).replaceWith(data);

                                    table = $('div.record-listing-container table.buic-listing');
                                    initEvents(table);

                                    /*
                                     Commented out for now - it has to be decided if functionality is wanted
                                    // Restore selection state.
                                    $(table).find('td input:checkbox[name="checkRow"]').each(function () {
                                        var id = $(this).parents('tr').attr('id').substr(5);

                                        if (id && selectedIds.indexOf(id) >= 0) {
                                            this.checked = true;
                                            rowSelection(this);
                                        }
                                    });
                                    $(table).find('tbody').each(function () {
                                        handleSelectionState(this);
                                    });
                                    */
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(jqXHR.status + ' (' + errorThrown + '):');
                                    console.log(JSON.parse(jqXHR.responseText));
                                },
                                dataType: 'html'
                            });
                        }
                    }
                }
            });
        }
    }

    /**
     * Handle row selection.
     *
     * @private
     * @static
     * @function rowSelection
     * @memberof Bolt.buic.listing
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
     * @memberof Bolt.buic.listing
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
