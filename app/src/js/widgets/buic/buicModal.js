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
                .attr('tabindex', -1)
                .attr('role', 'dialog')
                .addClass('buic-modal modal fade');
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
         * Sets the body of the modal.
         *
         * @param {Object|string} body - The body part of the modal.
         */
        body: function (body) {
            this.setBody = $('<div>').addClass('modal-body').append(body);
        },

        /**
         * Builds and shows the modal.
         */
        show: function () {
            var dialog = $('<div>').addClass('modal-dialog'),
                content = $('<div>').addClass('modal-content');

            // Add the body part.
            content.append(this.setBody);

            // Build the dialog.
            dialog.append(content);

            // Clear the modal.
            this._clear();

            this.element
                .append(dialog)
                .modal('show');
        }
    });
})(jQuery);
