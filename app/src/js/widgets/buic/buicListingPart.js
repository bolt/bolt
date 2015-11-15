/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC listingpart widget.
     *
     * tbody inside a listing widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicListingPart
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicListingPart', /** @lends jQuery.widget.bolt.buicListingPart.prototype */ {
        /**
         * The constructor of the listingpart widget.
         *
         * @private
         */
        _create: function () {
            var self = this;

            // Private properties
            this.listing = this.element.closest(':bolt-buicListing');
            this.toolbar = this.element.find('tr.selectiontoolbar');
            this.toolbarCount = this.toolbar.find('div.count');
            this.menu = this.element.find('tr.header th.menu');
            this.menuOnlyOnSelection = this.menu.find('li:not(.select-all)');
            this.menuSelectionCount = this.menu.find('li.dropdown-header');
            this.menuSelectionCountTemplate = this.menuSelectionCount.text();
            this.selectedIds = [];

            // Select all/none rows from menu.
            this.menu.find('li.select-all, li.select-none').on('click', function () {
                var set = $(this).hasClass('select-all');

                self.element.find('td input:checkbox[name="checkRow"]').each(function () {
                    this.checked = set;
                    self._update(this);
                });
            });

            // On check/unchecking a row selector.
            this.element.find('td input:checkbox[name="checkRow"]').on('click', function () {
                self._update(this);
            });

            // Record toolbar actions.
            this.toolbar.find('button[data-stb-cmd^="record:"]').on('click', function () {
                self._modifyRecords(
                    $(this).data('stb-cmd'),
                    self.selectedIds,
                    $(this).html()
                );
            });

            // Record row edit button actions.
            this.element.find('a[data-listing-cmd^="record:"]').on('click', function () {
                self._modifyRecords(
                    $(this).data('listing-cmd'),
                    [$(this).parents('tr').attr('id').substr(5)],
                    $(this).html()
                );
            });
        },

        /**
         * Tells the listing to modify selected records.
         *
         * @private
         * @param {string} action - Command to execute.
         * @param {array} ids - Checkbox clicked.
         * @param {string} buttonText - Button text to be displayed on ok button.
         */
        _modifyRecords: function (action, ids, buttonText) {
            if (ids.length > 0) {
                this.listing.buicListing('modifyRecords', action.replace(/^record:/, ''), ids, buttonText);
            }
        },

        /**
         * Update row, toolbar and menu on checkbox status change.
         *
         * @private
         * @param {object} checkbox - Checkbox clicked.
         */
        _update: function (checkbox) {
            var row = $(checkbox).closest('tr'),
                id = row.attr('id').substr(5),
                count;

            if (checkbox.checked) {
                this.selectedIds.push(id);
                row.addClass('row-selected');
            } else {
                this.selectedIds.splice(this.selectedIds.indexOf(id), 1);
                row.removeClass('row-selected');
            }
            count = this.selectedIds.length;

            // Show/hide toolbar & menu entries.
            if (count) {
                this.toolbar.removeClass('hidden');
                this.menuOnlyOnSelection.removeClass('hidden');
            } else {
                this.toolbar.addClass('hidden');
                this.menuOnlyOnSelection.addClass('hidden');
            }
            // Update selection count display.
            this.toolbarCount.text(count);
            // Update menuselection count display.
            this.menuSelectionCount.text(this.menuSelectionCountTemplate.replace('(#)', '(' + count + ')'));
        }
    });
})(jQuery);
