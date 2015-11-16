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
             */
            this._dialog = {
                body:   '',
                footer: ''
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
         * @param {Object|string} footer - The footer part of the modal.
         */
        footer: function (footer) {
            this._dialog.footer = $('<div>').addClass('modal-footer').append(footer);
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
         * Builds and shows the modal.
         */
        show: function () {
            var dialog =
                $('<div>')
                    .addClass('modal-dialog')
                    .append(
                        $('<div>')
                            .addClass('modal-content')
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
