/**
 * See (http://jquery.com/).
 * @name jQuery
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 */

/**
 * See (http://jquery.com/)
 * @name widget
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 * @memberOf jQuery
 */

/**
 * See (http://jquery.com/)
 * @name bolt
 * @class
 * @memberOf jQuery.widget
 * @param {object} $ - Global jQuery object
 * @param {object} bolt - Global Bolt object
 */
(function ($, bolt) {
    'use strict';

    /**
     * Bolt listing.
     *
     * @class listing
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.listing', /** @lends jQuery.widget.bolt.listing */ {
        /**
         * The constructor of the listing widget.
         *
         * @private
         */
        _create: function () {
            this.csrfToken  = this.element.data('bolt_csrf_token');
            this.contentType = this.element.data('contenttype');
            this.table = undefined;

            this._initTable();
        },

        /**
         * Initialize events of a listing table.
         *
         * @private
         */
        _initTable: function () {
            var self = this;

            this.table = this.element.find('table.listing');

            // Select all rows in a listing section.
            this.table.find('tr.header th.menu li.select-all a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = true;
                    self._rowSelection(this);
                });
                self._handleSelectionState(this);
            });

            // Unselect all rows in a listing section.
            this.table.find('tr.header th.menu li.select-none a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = false;
                    self._rowSelection(this);
                });
                self._handleSelectionState(this);
            });

            // On check/unchecking a row selector.
            this.table.find('td input:checkbox[name="checkRow"]').on('click', function () {
                self._rowSelection(this);
                self._handleSelectionState(this);
            });

            // Record toolbar actions.
            this.table.find('tr.selectiontoolbar button[data-stb-cmd^="record:"]').each(function () {
                $(this).on('click', function () {
                    self._modifyRecords(this, $(this).data('stb-cmd').replace(/^record:/, ''));
                });
            });

            // Record row edit button actions.
            this.table.find('a[data-listing-cmd^="record:"]').each(function () {
                var id = $(this).parents('tr').attr('id').substr(5);

                $(this).on('click', function () {
                    self._modifyRecords(this, $(this).data('listing-cmd').replace(/^record:/, ''), [id]);
                });
            });
        },

        /**
         * Hide/Show selection toolbar.
         *
         * @param {object} element - Element inside a tbody.
         */
        _handleSelectionState: function (element) {
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
        },

        /**
         * Execute commands on triggered button.
         *
         * @param {object} button - Triggered list button.
         * @param {string} action - Triggered action (Allowed: 'delete').
         * @param {array} ids - Optional array of ids to perform the action on.
         */
        _modifyRecords: function (button, action, ids) {
            var self = this,
                tbody = $(button).closest('tbody'),
                checkboxes = tbody.find('td input:checkbox[name="checkRow"]:checked'),
                selectedIds = [],
                modifications = {},
                actions = {
                    'delete': {
                        'name': bolt.data('recordlisting.action.delete'),
                        'cmd': {'delete': null}
                    },
                    'publish': {
                        'name': bolt.data('recordlisting.action.publish'),
                        'cmd': {'modify': {'status': 'published'}}
                    },
                    'depublish': {
                        'name': bolt.data('recordlisting.action.depublish'),
                        'cmd': {'modify': {'status': 'held'}}
                    },
                    'draft': {
                        'name': bolt.data('recordlisting.action.draft'),
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
                modifications[self.contentType] = {};
                $(selectedIds).each(function () {
                    modifications[self.contentType][this] = actions[action].cmd;
                });

                // Build message:
                if (selectedIds.length === 1) {
                    msg = bolt.data('recordlisting.confirm.one');
                } else {
                    msg = bolt.data('recordlisting.confirm.multi', {'%NUMBER%': '<b>' + selectedIds.length + '</b>'});
                }
                msg = msg + '<br><br><b>' + bolt.data('recordlisting.confirm.no-undo') + '</b>';

                // Remove when done:
                msg = msg + '<hr><b style="color:red;">Anti CSRF token functionality still disabled ' +
                    'in Bolt\Controller\Async\Records::modify</b>';

                bootbox.dialog({
                    message: msg,
                    title: actions[action].name,
                    buttons: {
                        cancel: {
                            label: bolt.data('recordlisting.action.cancel'),
                            className: 'btn-default'
                        },
                        ok: {
                            label: buttonText,
                            className: 'btn-primary',
                            callback: function () {
                                var url = bolt.conf('paths.async') + 'content/action' + window.location.search;

                                $.ajax({
                                    url: url,
                                    type: 'POST',
                                    data: {
                                        'bolt_csrf_token': self.csrfToken,
                                        'contenttype': self.contentType,
                                        'actions': modifications
                                    },
                                    success: function (data) {
                                        self.element.html(data);
                                        self._initTable();

                                        /*
                                         Commented out for now - it has to be decided if functionality is wanted
                                        // Restore selection state.
                                        $(table).find('td input:checkbox[name="checkRow"]').each(function () {
                                            var id = $(this).parents('tr').attr('id').substr(5);

                                            if (id && selectedIds.indexOf(id) >= 0) {
                                                this.checked = true;
                                                self._rowSelection(this);
                                            }
                                        });
                                        $(table).find('tbody').each(function () {
                                            self._handleSelectionState(this);
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
        },

        /**
         * Handle row selection.
         *
         * @param {object} checkbox - Checkbox clicked.
         */
        _rowSelection: function (checkbox) {
            var row = $(checkbox).closest('tr');

            if (checkbox.checked) {
                row.addClass('row-selected');
            } else {
                row.removeClass('row-selected');
            }
        }
    });
})(jQuery, Bolt);
