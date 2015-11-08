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
 */
(function ($) {
    'use strict';

    /**
     * Bolt listingpart - tbody inside a listing.
     *
     * @class listingpart
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.listingpart', /** @lends jQuery.widget.bolt.listingpart */ {
        /**
         * The constructor of the listingpart widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            this.listing = this.element.closest(':bolt-listing');

            // Select all rows in a listing section.
            this.element.find('tr.header th.menu li.select-all a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = true;
                    self._rowSelection(this);
                });
                self._handleSelectionState(this);
            });

            // Unselect all rows in a listing section.
            this.element.find('tr.header th.menu li.select-none a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = false;
                    self._rowSelection(this);
                });
                self._handleSelectionState(this);
            });

            // On check/unchecking a row selector.
            this.element.find('td input:checkbox[name="checkRow"]').on('click', function () {
                self._rowSelection(this);
                self._handleSelectionState(this);
            });

            // Record toolbar actions.
            this.element.find('tr.selectiontoolbar button[data-stb-cmd^="record:"]').each(function () {
                $(this).on('click', function () {
                    var selectedIds = self._selected();

                    if (selectedIds.length > 0) {
                        self.listing.listing(
                            'modifyRecords',
                            this,
                            $(this).data('stb-cmd').replace(/^record:/, ''),
                            selectedIds
                        );
                    }
                });
            });

            // Record row edit button actions.
            this.element.find('a[data-listing-cmd^="record:"]').each(function () {
                var id = $(this).parents('tr').attr('id').substr(5);

                $(this).on('click', function () {
                    self.listing.listing(
                        'modifyRecords',
                        this,
                        $(this).data('listing-cmd').replace(/^record:/, ''),
                        [id]
                    );
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
        },

        /**
         * Returns all selected ids.
         *
         * @returns {Array}
         */
        _selected: function () {
            var selectedIds = [];

            this.element.find('td input:checkbox[name="checkRow"]:checked').each(function () {
                var id = $(this).parents('tr').attr('id').substr(5);

                if (id) {
                    selectedIds.push(id);
                }
            });

            return selectedIds;
        }
    });
})(jQuery);
