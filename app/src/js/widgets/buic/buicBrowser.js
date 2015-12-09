/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * BUIC filebrowser widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicBrowser
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicBrowser', /** @lends jQuery.widget.bolt.buicBrowser.prototype */ {
        /**
         * Default options.
         *
         * @property {string} [url] - URL to browse
         */
        options: {
            namespace: '',
            path: ''
        },

        /**
         * The constructor of the filebrowser widget.
         *
         * @private
         */
        _create: function () {

            /**
             * The current url.
             *
             * @type {string}
             * @name _url
             * @memberOf jQuery.widget.bolt.buicBrowser.prototype
             * @private
             */
            this._url = bolt.conf('paths.async') + 'browse/' + this.options.namespace +
                            (this.options.path ? '/' + this.options.path : '');

            this._on({
                'click': function() {
                    this._browse();
                }
            });
        },

        /**
         * Browser directory on server.
         *
         * @private
         */
        _browse: function () {
            var self = this,
                data = {};

            $('body').buicModal({
                size: 'large',
                remote: {
                    url:  self._url,
                    data: data
                },
                loaded: function (evt, modal) {
                    // Set data structures
                    modal.body.find('.entry').each(function () {
                        var tr = this.closest('tr'),
                            name = $(this).text().trim(),
                            ext = name.match(/\.(.+?[^/])$/);

                        $(tr)
                            .attr('data-bolt-browse-name', name)
                            .attr('data-bolt-browse-ext', ext ? ext[1] : '');
                    });

                    // Set up event handler
                    modal.header
                        .on('click.bolt', '[data-fbrowser-chdir]', function () {
                            self._url = $(this).data('fbrowser-chdir');
                            self._browse();
                        });
                    modal.body
                        .on('click.bolt', '[data-fbrowser-chdir]', function () {
                            self._url = $(this).data('fbrowser-chdir');
                            self._browse();
                        })
                        .on('click.bolt', '[data-fbrowser-select]', function () {
                            self._select($(this).data('fbrowser-select'));
                            modal.close();
                        })
                        .on('click.bolt', '[aria-pressed]', function (evt) {
                            var activated = $(this).attr('aria-pressed') === 'false',
                                type = this.className.replace('toogle-', '');

                            $(this).attr('aria-pressed', activated ? 'true' : 'false');
                            modal.body.find('tbody.' + type).toggleClass('hidden', !activated);

                            if (evt.clientX > 0) {
                                this.blur();
                            }
                        })
                        .on('change', 'select[name="ext"]', function () {
                            self._filter(modal);
                        })
                        .keyup('input[name="filter"]', function () {
                            self._filter(modal);
                        });
                }
            });
        },

        /**
         * Filter displaxed files and folders.
         *
         * @private
         * @param {Object} modal - The modal dialog object
         */
        _filter: function (modal) {
            var term = modal.body.find('input[name="filter"]').val(),
                ext = modal.body.find('select[name="ext"]').val(),
                name,
                hide;

            modal.body.find('[data-bolt-browse-name]').each(function () {
                name = $(this).data('bolt-browse-name');
                hide = ext !== '' && $(this).data('bolt-browse-ext') !== ext ||
                       term !== '' && name.search(term) < 0;
                $(this).toggleClass('hidden', hide);

                if (!hide) {
                    $(this).find('a, span').each(function () {
                        var text = name.replace(term, '<mark>' + term + '</mark>');
                        console.log($(this).html(text));
                    });
                }
            });
        },

        /**
         * Select file in modal file selector dialog.
         *
         * @private
         * @param {string} path - Path to the selected file
         */
        _select: function (path) {
            var fieldset = this.element.closest('fieldset');

            if (fieldset.is(':bolt-fieldFile') || fieldset.is(':bolt-fieldImage')) {
                $('input.path', fieldset).val(path).trigger('change');
            } else if (fieldset.is(':bolt-fieldFilelist') || fieldset.is(':bolt-fieldImagelist')) {
                bolt.uploads.addToList(fieldset, path);
            } else if (fieldset.is(':bolt-buicStack')) {
                fieldset.buicStack('add', path);
            }
        }
    });
})(jQuery, Bolt);
