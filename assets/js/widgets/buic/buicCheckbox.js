/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC checkbox widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicCheckbox
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicCheckbox', /** @lends jQuery.widget.bolt.buicCheckbox.prototype */ {
        /**
         * The constructor of the checkbox widget.
         *
         * @private
         */
        _create: function () {
            var button = this.element.find('button'),
                state = this.element.find('input');

            this._on(button, {
                'click': function () {
                    state.prop('checked', !state.prop('checked'));
                }
            });
        }
    });
})(jQuery);
