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
                var row = $(this).closest('tr');

                this.checked = setStatus;

                if (setStatus) {
                    row.addClass('row-checked');
                } else {
                    row.removeClass('row-checked');
                }
            });
        });
    };

    // Apply mixin container
    bolt.buic.listing = listing;

})(Bolt || {}, jQuery);
