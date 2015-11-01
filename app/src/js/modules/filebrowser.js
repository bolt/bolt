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
                        bolt.stack.select(
                            $(this).closest('[data-fbrowser-fieldid]').data('fbrowser-fieldid'),
                            $(this).data('fbrowser-select')
                        );
                    })
                    // Show dialog.
                    .show();
            }
        });
    }

    // Apply mixin container
    bolt.filebrowser = filebrowser;

})(Bolt || {}, jQuery);
