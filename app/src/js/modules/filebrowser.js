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
            function (event) {
                browse($(event.relatedTarget).data('modal-source'));
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
     * @param {string} url - The URL to load into the file browser window.
     * @param {boolean} change - Reload on "change folder".
     */
    function browse(url, change) {
        var fieldId = url.match(/\?fieldid=(.+?)$/)[1];

        if (change || !history[fieldId]) {
            history[fieldId] = url;
        }

        $('#modal-server-select .modal-dialog').load(history[fieldId] + ' .modal-content', function (response, status) {
            if (status === 'success' || status === 'notmodified') {
                $('#modal-server-select')
                    // Init change folder action.
                    .find('[data-fbrowser-chdir]').on('click', function (evt) {
                        evt.preventDefault();
                        browse($(this).data('fbrowser-chdir'), true);
                    })
                    .end()
                    // Init file select action.
                    .find('[data-fbrowser-select]').on('click', function (evt) {
                        evt.preventDefault();
                        select(
                            $(this).closest('[data-fbrowser-fieldid]').data('fbrowser-fieldid'),
                            $(this).data('fbrowser-select')
                        );
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
    function select(fieldid, path) {
        var container = $('#' + fieldid);

        switch (container.data('bolt-field')) {
            case 'file':
            case 'image':
                $('input.path', container).val(path).trigger('change');
                break;
            case 'filelist':
                bolt.uploads.addToList(container, path, path);
                break;
            case 'imagelist':
                bolt.uploads.addToList(container, path, path);
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
