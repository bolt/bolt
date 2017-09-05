/**
 * @param {Object} $    - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Imagelist field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldImagelist
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.fieldFilelist
     */
    $.widget('bolt.fieldImagelist', $.bolt.fieldFilelist, /** @lends jQuery.widget.bolt.fieldImagelist.prototype */ {
        /**
         * The constructor of the imagelist field widget.
         *
         * @private
         */
        _create: function () {
            // Mark this widget as type of "ImageList".
            this.options.isImage = true;

            // Call the parent constructor.
            this._super();
        }
    });
})(jQuery);
