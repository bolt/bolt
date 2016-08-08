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
         * Event reporting that a file was selected.
         *
         * @event jQuery.widget.bolt.buicBrowser#buicbrowserselected
         * @property {string} path - The path to the selected file
         */

        /**
         * Default options.
         *
         * @property {string} [url] - URL to browse
         */
        options: {
            namespace: '',
            path: '',
            multiselect: false
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
                'click': function () {
                    this._browse();
                }
            });
        },

        /**
         * Browser directory on server.
         *
         * @private
         * @fires jQuery.widget.bolt.buicBrowser#buicbrowserselected
         */
        _browse: function () {
            var self = this,
                data = {
                    multiselect: this.options.multiselect
                },
                files = [],
                allChecked = false;

            $('body').buicModal({
                size: 'large',
                remote: {
                    url:  self._url,
                    data: data
                },
                loaded: function (evt, modal) {

                    // Find the add selected button
                    var addSelectedBtn = modal.footer.find('[data-fbrowser-add-checked]');

                    // Set data structures
                    modal.body.find('.entry').each(function () {
                        var tr = $(this).closest('tr'),
                            name = $(this).text().trim(),
                            ext = name.match(/\.(.+?[^/])$/);

                        $(tr)
                            .attr('data-bolt-browse-name', name)
                            .attr('data-bolt-browse-ext', ext ? ext[1] : '');
                    });

                    // Set up event handler
                    modal.header
                        .on('click.bolt', '[data-fbrowser-chdir]', function (evt) {
                            evt.preventDefault();
                            self._url = $(this).data('fbrowser-chdir');
                            self._browse();
                        });
                    modal.body
                        .on('click.bolt', '[data-fbrowser-chdir]', function (evt) {
                            evt.preventDefault();
                            self._url = $(this).data('fbrowser-chdir');
                            self._browse();
                        })
                        .on('click.bolt', '[data-fbrowser-select]', function (evt) {
                            evt.preventDefault();
                            self._trigger('selected', null, {path: $(this).data('fbrowser-select')});
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
                        })
                        .on('change', '[data-fbrowser-check]', function () {
                            var fileIndex = files.indexOf($(this).data('fbrowser-check'));
                            if (fileIndex > -1) {
                                files.splice(fileIndex, 1);
                                allChecked = false;
                                if (files.length === 0) {
                                    addSelectedBtn.addClass('disabled');
                                }
                            } else {
                                files.push($(this).data('fbrowser-check'));
                                addSelectedBtn.removeClass('disabled');
                            }
                        });
                    modal.footer
                        .on('click.bolt', '.toggle-all', function (evt) {
                            evt.preventDefault();
                            if (!allChecked) {
                                modal.body.find('[data-fbrowser-check]').each(function () {
                                    $(this).prop('checked', true);
                                    files.push($(this).data('fbrowser-check'));
                                });
                                allChecked = true;
                                addSelectedBtn.removeClass('disabled');
                            } else {
                                modal.body.find('[data-fbrowser-check]').each(function () {
                                    $(this).prop('checked', false);
                                });
                                files.length = 0;
                                allChecked = false;
                                addSelectedBtn.addClass('disabled');
                            }
                        })
                        .on('click.bolt', '[data-fbrowser-add-checked]:not(.disabled)', function (evt) {
                            evt.preventDefault();
                            files.forEach(function (filePath) {
                                self._trigger('selected', null, {path: filePath});
                            });
                            modal.close();
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
                        $(this).html(name.replace(term, '<mark>' + term + '</mark>'));
                    });
                }
            });
        }
    });
})(jQuery, Bolt);
