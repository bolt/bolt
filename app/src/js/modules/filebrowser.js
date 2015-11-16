/**
 * Filebrowser functionality.
 *
 * @mixin
 * @namespace Bolt.filebrowser
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.filebrowser mixin container.
     *
     * @private
     * @type {Object}
     */
    var filebrowser = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.filebrowser
     */
    filebrowser.init = function () {
        // Initialze file browser modal.
        $('#modal-server-select').on(
            'show.bs.modal',
            function (evt) {
                var button = $(evt.relatedTarget);

                browse(button, button.data('modal-source'));
            }
        );
    };

    /**
     * Changes folder in modal file selector dialog.
     *
     * @private
     * @function browse
     * @memberof Bolt.filebrowser
     *
     * @param {Object} button
     */
    function browse(button) {
        $('#modal-server-select .modal-dialog')
            .load(button.data('modal-source') + ' .modal-content', function (response, status) {
                if (status === 'success' || status === 'notmodified') {
                    $('#modal-server-select')
                        // Init change folder action.
                        .find('[data-fbrowser-chdir]').on('click', function (evt) {
                            evt.preventDefault();
                            button.data('modal-source', $(this).data('fbrowser-chdir'));
                            browse(button);
                        })
                        .end()
                        // Init file select action.
                        .find('[data-fbrowser-select]').on('click', function (evt) {
                            evt.preventDefault();
                            select(button.closest('fieldset'), $(this).data('fbrowser-select'));
                        })
                        // Show dialog.
                        .show();
                }
            });
    }

    /**
     * Select file in modal file selector dialog.
     *
     * @private
     * @function select
     * @memberof Bolt.filebrowser
     *
     * @param {Object} fieldset - Fieldset
     * @param {string} path - Path to the selected file
     */
    function select(fieldset, path) {
        if (fieldset.is(':bolt-fieldFile') || fieldset.is(':bolt-fieldImage')) {
            $('input.path', fieldset).val(path).trigger('change');
        } else if (fieldset.is(':bolt-fieldFilelist') || fieldset.is(':bolt-fieldImagelist')) {
            bolt.uploads.addToList(fieldset, path);
        } else {
            bolt.stack.addToStack(path);
        }

        // Close the modal dialog.
        $('#modal-server-select').modal('hide');
    }

    // Apply mixin container
    bolt.filebrowser = filebrowser;

})(Bolt || {}, jQuery);
