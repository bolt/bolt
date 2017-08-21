/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($) {
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
            url: '',
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
            this._url = this.options.url;

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
                        .on('click.bolt', '[data-file] a', function (evt) {
                            evt.preventDefault();
                            var file = $(this).closest('[data-file]').data('file');
                            self._trigger('selected', null, file);
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
                            var file = $(this).closest('[data-file]').data('file');
                            var fileIndex = files.indexOf(file.fullPath);
                            if (fileIndex > -1) {
                                files.splice(fileIndex, 1);
                                allChecked = false;
                                if (files.length === 0) {
                                    addSelectedBtn.addClass('disabled');
                                }
                            } else {
                                files.push(file.fullPath);
                                addSelectedBtn.removeClass('disabled');
                            }
                        });
                    modal.footer
                        .on('click.bolt', '.toggle-all', function (evt) {
                            evt.preventDefault();
                            if (!allChecked) {
                                modal.body.find('[data-fbrowser-check]').each(function () {
                                    $(this).prop('checked', true);
                                    var file = $(this).closest('[data-file]').data('file');
                                    files.push(file.fullPath);
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
                            modal.body.find('[data-fbrowser-check]').each(function () {
                                if (!$(this).prop('checked')) {
                                    return;
                                }
                                var file = $(this).closest('[data-file]').data('file');
                                self._trigger('selected', null, file);
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
         *
         * TODO this only marks the first occurence of the string, swap out for Regex solution
         */
        _filter: function (modal) {
            var term = modal.body.find('input[name="filter"]').val(),
                ext = modal.body.find('select[name="ext"]').val(),
                file,
                hide;

            modal.body.find('[data-file],[data-folder]').each(function () {
                file = $(this).data('file') || $(this).data('folder');
                hide = ext !== '' && file.extension !== ext ||
                       term !== '' && file.filename.search(term) < 0;

                $(this).toggleClass('hidden', hide);

                if (!hide) {
                    $(this).find('a, span').each(function () {
                        $(this).html(file.filename.replace(term, '<mark>' + term + '</mark>'));
                    });
                }
            });
        }
    });
})(jQuery, Bolt);
