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
     * Bolt checkbox.
     *
     * @class checkbox
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
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
