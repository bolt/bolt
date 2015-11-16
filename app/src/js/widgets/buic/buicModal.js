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
            /**
             * Dialog elements.
             *
             * @type {Object}
             * @name _dialog
             * @memberOf jQuery.widget.bolt.buicModal.prototype
             * @private
             *
             * @property {Object} body   - Dialog body
             * @property {Object} footer - Dialog footer
             * @property {Object} header - Dialog header
             */
            this._dialog = {
                body:   '',
                footer: '',
                header: ''
            };

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
            this._dialog.body = $('<div>').addClass('modal-body').append(body);
        },

        /**
         * Sets the body of the modal.
         *
         * @param {Object|string} footer - The footer part of the modal.
         */
        footer: function (footer) {
            this._dialog.footer = $('<div>').addClass('modal-footer').append(footer);
        },

        /**
         * Sets the header of the modal.
         *
         * @param {Object|string} headline       - Add a headline to the modal
         * @param {boolean}       [closer=false] - Add a close button
         */
        header: function (headline, closer) {

            var hd = $('<h4>').addClass('modal-title').append(headline),
                cb = '';

            if (closer) {
                cb = '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                         '<span aria-hidden="true">&times;</span>' +
                     '</button>';
            }

            this._dialog.header = $('<div>').addClass('modal-header').append(cb).append(hd);
        },

        /**
         * Builds and shows the modal.
         */
        show: function () {
            var dialog =
                $('<div>')
                    .addClass('modal-dialog')
                    .attr('role', 'document')
                    .append(
                        $('<div>')
                            .addClass('modal-content')
                            .append(this._dialog.header)
                            .append(this._dialog.body)
                            .append(this._dialog.footer)
                    );

            // Clear the modal.
            this._clear();

            this.element
                .append(dialog)
                .modal('show');
        }
    });
})(jQuery);
