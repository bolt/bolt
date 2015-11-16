/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC modal widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicModal
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicModal', /** @lends jQuery.widget.bolt.buicModal.prototype */ {
        /**
         * The constructor of the modal widget.
         *
         * @private
         */
        _create: function () {
            this.element
                .attr('id', 'bolt-modal')
                .addClass('buic-modal');

            this._init();
        },

        /**
         * Clears the modal.
         *
         * @private
         */
        _clear: function () {
            this.element
                .removeData('bs.modal')
                .empty();
        },

        /**
         * Initialzes the modal.
         *
         * @private
         */
        _init: function () {
            this._clear();

            this.element
                .append(
                    '<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">' +
                        '<div class="modal-dialog">' +
                            '<div class="modal-content">' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                )
                .modal({show: false});
        }
    });
})(jQuery);
