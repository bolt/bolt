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
            this.toolbar.find('button[data-stb-cmd^="record:"]').each(function () {
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
