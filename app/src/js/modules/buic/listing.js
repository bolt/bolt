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
        // Select/unselect all rows in a listing section.
        $(buic).find('tr.header input:checkbox[name="checkRow"]').on('click', function () {
            var setStatus = this.checked;

            $(this).closest('tbody').find('td input:checkbox[name="checkRow"]').each(function () {
                this.checked = setStatus;
                rowSelection(this);
            });
            toogleSelectionToolbar(this);
        });

        // On check/unchecking a row selector.
        $(buic).find('td input:checkbox[name="checkRow"]').on('click', function () {
            rowSelection(this);
            toogleSelectionToolbar(this);
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
     * @function toogleSelectionToolbar
     * @memberof Bolt.files
     *
     * @param {object} element - Element inside a tbody.
     */
    function toogleSelectionToolbar(element) {
        var tbody = $(element).closest('tbody'),
            toolbar = tbody.find('tr.selectiontoolbar').first(),
            count = tbody.find('td input:checkbox[name="checkRow"]:checked').length;

        if (count) {
            toolbar.removeClass('hidden');
        } else {
            toolbar.addClass('hidden');
        }
    }

    // Apply mixin container
    bolt.buic.listing = listing;

})(Bolt || {}, jQuery);
