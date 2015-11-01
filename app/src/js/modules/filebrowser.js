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
     * Remember last opened url by key.
     *
     * @private
     * @type {Object}
     */
    var history = {};

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

                browse(button.closest('fieldset'), button.data('modal-source'), false);
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
     * @param {fieldset} fieldset
     * @param {string} url - The URL to load into the file browser window.
     * @param {boolean} change - Reload on "change folder".
     */
    function browse(fieldset, url, change) {
        var fieldId = $(fieldset).attr('id');

        if (change || !history[fieldId]) {
            history[fieldId] = url;
        }

        $('#modal-server-select .modal-dialog').load(history[fieldId] + ' .modal-content', function (response, status) {
            if (status === 'success' || status === 'notmodified') {
                $('#modal-server-select')
                    // Init change folder action.
                    .find('[data-fbrowser-chdir]').on('click', function (evt) {
                        evt.preventDefault();
                        browse(fieldset, $(this).data('fbrowser-chdir'), true);
                    })
                    .end()
                    // Init file select action.
                    .find('[data-fbrowser-select]').on('click', function (evt) {
                        evt.preventDefault();
                        select(fieldset, $(this).data('fbrowser-select'));
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
     * @param {string} fieldid - Id of the fieldset
     * @param {string} path - Path to the selected file
     */
    function select(fieldset, path) {
        switch (fieldset.data('bolt-field')) {
            case 'file':
            case 'image':
                $('input.path', fieldset).val(path).trigger('change');
                break;
            case 'filelist':
                bolt.uploads.addToList(fieldset, path, path);
                break;
            case 'imagelist':
                bolt.uploads.addToList(fieldset, path, path);
                break;
            default:
                bolt.stack.addToStack(path);
        }

        // Close the modal dialog.
        $('#modal-server-select').modal('hide');
    }

    // Apply mixin container
    bolt.filebrowser = filebrowser;

})(Bolt || {}, jQuery);
