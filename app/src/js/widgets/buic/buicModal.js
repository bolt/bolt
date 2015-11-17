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
         * Builds and shows the modal.
         *
         * @param {Object}        def                - Definition of the modal
         * @param {boolean}       [def.closer=false] - Add a close button
         * @param {Object|string} [def.headline]     - Add a headline
         * @param {Object|string} [def.body]         - Add a body
         * @param {Object|string} [def.footer]       - Add a footer
         */
        go: function (def) {
            var self = this,
                dialog,
                dialogHeader = '',
                dialogBody = '',
                dialogFooter = '',
                closer = '',
                headline = '';

            // Header
            if (def.closer || def.headline) {
                if (def.closer) {
                    closer = '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                                '<span aria-hidden="true">&times;</span>' +
                             '</button>';
                }

                if (def.headline) {
                    headline = $('<h4>').addClass('modal-title').append(def.headline);
                }

                dialogHeader = $('<div>').addClass('modal-header').append(closer).append(headline);
            }

            // Body
            dialogBody = $('<div>').addClass('modal-body').append(def.body || '');

            // Footer
            if (def.footer) {
                dialogFooter = $('<div>').addClass('modal-footer').append(def.footer);
            }

            // Dialog
            dialog =
                $('<div>')
                    .addClass('modal-dialog')
                    .attr('role', 'document')
                    .append(
                        $('<div>')
                            .addClass('modal-content')
                            .append(dialogHeader)
                            .append(dialogBody)
                            .append(dialogFooter)
                    );

            // Clear the modal.
            this._clear();

            this.element
                .append(dialog)
                .on('show.bs.modal', function () {
                    self._trigger('show');
                })
                .on('shown.bs.modal', function () {
                    self._trigger('shown');
                })
                .on('hide.bs.modal', function () {
                    self._trigger('hide');
                })
                .on('hidden.bs.modal', function () {
                    self._trigger('hidden');
                    self._clear();
                })
                .modal('show');
        }
    });
})(jQuery);
