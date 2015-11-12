/**
 * Meta field widget.
 *
 * @param {object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Text field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldMeta
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldMeta', /** @lends jQuery.widget.bolt.fieldMeta */ {
        /**
         * The constructor of the meta field widget.
         *
         * @private
         */
        _create: function () {
            var statusselect = this.element.find('#statusselect'),
                ownerid = this.element.find('#ownerid');

            statusselect.select2({
                width: '50%',
                minimumResultsForSearch: Infinity
            });

            ownerid.select2({
                width: '50%',
                minimumResultsForSearch: Infinity
            });
        }
    });
})(jQuery);
