/**
 * BUIC checkbox widget.
 *
 * @param {object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC checkbox widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class checkbox
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.checkbox', /** @lends jQuery.widget.bolt.checkbox */ {
        /**
         * The constructor of the checkbox widget.
         *
         * @private
         */
        _create: function () {
            var button = this.element.find('button'),
                state = this.element.find('input');

            button.on('click', function () {
                state.prop('checked', !state.prop('checked'));
            });
        }
    });
})(jQuery);
