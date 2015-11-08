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

            // Private properties
            this.listing = this.element.closest(':bolt-listing');
            this.selectedIds = [];

            // Select all rows in a listing section.
            this.element.find('tr.header th.menu li.select-all a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = true;
                    self._toogleSelection(this);
                });
                self._handleSelectionState(this);
            });

            // Unselect all rows in a listing section.
            this.element.find('tr.header th.menu li.select-none a').on('click', function () {
                $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = false;
                    self._toogleSelection(this);
                });
                self._handleSelectionState(this);
            });

            // On check/unchecking a row selector.
            this.element.find('td input:checkbox[name="checkRow"]').on('click', function () {
                self._toogleSelection(this);
                self._handleSelectionState(this);
            });

            // Record toolbar actions.
            this.element.find('tr.selectiontoolbar button[data-stb-cmd^="record:"]').each(function () {
                $(this).on('click', function () {
                    if (self.selectedIds.length > 0) {
                        self.listing.listing(
                            'modifyRecords',
                            this,
                            $(this).data('stb-cmd').replace(/^record:/, ''),
                            self.selectedIds
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
         * @private
         * @param {object} element - Element inside a tbody.
         */
        _handleSelectionState: function (element) {
            var tbody = $(element).closest('tbody'),
                menu = tbody.find('tr.header th.menu'),
                menuSel = menu.find('li.dropdown-header'),
                toolbar = tbody.find('tr.selectiontoolbar'),
                count = this.selectedIds.length,
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
         * Toogle if a row is selected.
         *
         * @private
         * @param {object} checkbox - Checkbox clicked.
         */
        _toogleSelection: function (checkbox) {
            var row = $(checkbox).closest('tr'),
                id = row.attr('id').substr(5);

            if (checkbox.checked) {
                this.selectedIds.push(id);
                row.addClass('row-selected');
            } else {
                this.selectedIds.splice(this.selectedIds.indexOf(id), 1);
                row.removeClass('row-selected');
            }
        }
    });
})(jQuery);
