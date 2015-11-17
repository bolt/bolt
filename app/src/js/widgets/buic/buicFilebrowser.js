/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC filebrowser widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicFilebrowser
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicFilebrowser', /** @lends jQuery.widget.bolt.buicFilebrowser.prototype */ {
        /**
         * The constructor of the filebrowser widget.
         *
         * @private
         */
        _create: function () {
            this._on({
                'click': function() {
                }
            });
        }
    });
})(jQuery);
