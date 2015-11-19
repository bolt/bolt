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
         * Default options.
         *
         * @property {boolean}       [small=false]   - Create a small dialog
         * @property {boolean}       [large=false]   - Create a large dialog
         * @property {boolean}       [closer=false]  - Add a close button
         * @property {Object|string} [headline]      - Add a headline (If set, the header is build out of it and closer)
         * @property {Object|string} [header]        - Add a header
         * @property {Object|string} [body]          - Add a body
         * @property {Object|string} [footer]        - Add a footer
         * @property {string}        [remote]        - Add a URL to load content from
         * @property {string}        [remote.url]    - Remote URL
         * @property {Object}        [remote.params] - Remote URL
         * @property {function}      [loaded]        - Callback fired when remote data was laoded
         */
        options: {
            small:    false,
            large:    false,
            closer:   false,
            headline: undefined,
            header:   undefined,
            body:     undefined,
            footer:   undefined,
            remote:   undefined,
            loaded:   undefined
        },

        /**
         * The constructor of the modal widget.
         *
         * @private
         */
        _create: function () {
            var self =    this,
                header =  $('<div class="modal-header hidden"/>'),
                body =    $('<div class=modal-body/>'),
                footer =  $('<div class="modal-footer hidden"/>'),
                content = $('<div class=modal-content/>').append(header, body, footer),
                dialog =  $('<div class=modal-dialog role=document/>').append(content),
                modal =   $('<div tabindex=-1 role=dialog class="modal fade buic-modal"/>').append(dialog);

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} header - Header element of the modal
             * @property {Object} body   - Body element of the modal
             * @property {Object} footer - Footer element of the modal
             * @property {Object} modal  - The modal
             */
            this._ui = {
                header:  header,
                body:    body,
                footer:  footer,
                content: content,
                modal:   modal
            };

            dialog
                .toggleClass('modal-sm', self.options.small)
                .toggleClass('modal-lg', self.options.large);

            // Build and add content.
            if (this.options.remote) {
                this._load();
            } else {
                this._setHeader();
                this._setBody();
                this._setFooter();
            }

            // Retry button.
            this._on(this.element, {
                'click .modal-retry': function () {
                    self._load();
                }
            });

            // Add the modal to the DOM.
            self.element.prepend(self._ui.modal);

            // Activate bootstrap modal.
            self._ui.modal
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
                    self.destroy();
                })
                .modal('show');
        },

        /**
         * Cleaning up.
         *
         * @private
         */
        _destroy: function () {
            this._ui.modal
                .data('modal', null)
                .remove();
        },

        /**
         * Sets the header of the modal.
         *
         * @param {Object|string} [header] - Header element to set
         */
        _setHeader: function (header) {
            if (this.options.headline !== undefined) {
                this.options.header = $();

                if (this.options.closer) {
                    this.options.header = this.options.header.add(
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>'
                    );
                }

                this.options.header = this.options.header
                    .add($('<h4 class="modal-title"/>')
                    .append(this.options.headline));
            }

            header =
                (header === undefined ? '' : header) ||
                $('<div>').append(this.options.header).html() ||
                '';

            this._ui.header
                .off()
                .toggleClass('hidden', !header.length)
                .html(header);
        },

        /**
         * Sets the body of the modal.
         *
         * @param {Object|string} [body] - Body element to set
         */
        _setBody: function (body) {
            this._ui.body
                .off()
                .html(
                    (body === undefined ? '' : body) ||
                    $('<div>').append(this.options.body).html() ||
                    ''
                );
        },

        /**
         * Sets the footer of the modal.
         *
         * @param {Object|string} [footer] - Footer element to set
         */
        _setFooter: function (footer) {
            footer =
                (footer === undefined ? '' : footer) ||
                $('<div>').append(this.options.footer).html() ||
                '';

            this._ui.footer
                .off()
                .toggleClass('hidden', !footer.length)
                .html(footer);
        },

        /**
         * Loads content from a url.
         */
        _load: function () {
            var self = this;

            self._ui.content.addClass('modal-loading');

            $.get(self.options.remote.url, self.options.remote.params || {})
                .done(function (data) {
                    self.options.header = undefined;
                    self.options.body = undefined;
                    self.options.footer = undefined;

                    $(data).children('header, main, footer').each(function () {
                        self.options[$(this)[0].tagName.toLowerCase().replace('main', 'body')] = $(this)[0].innerHTML;
                    });

                    self._setHeader();
                    self._setBody();
                    self._setFooter();

                    self._trigger(
                        'loaded',
                        null,
                        {
                            header:  self._ui.header,
                            body:    self._ui.body,
                            footer:  self._ui.footer
                        }
                    );
                })
                .fail(function () {
                    self.options.header = undefined;
                    self.options.body =
                        '<button type=button class="btn btn-default modal-retry">' +
                            '<i class="fa fa-refresh"></i>' +
                        '</button>';
                    self.options.footer = undefined;

                    self._setHeader();
                    self._setBody();
                    self._setFooter();
                })
                .always(function () {
                    self._ui.content.removeClass('modal-loading');
                });
        },

        /**
         * Render if remote option is changed.
         *
         * @param {string} key   - Option key
         * @param {*}      value - Option value
         */
        _setOption: function (key, value) {
            var render = key === 'remote';

            this._super(key, value);

            if (render) {
                this._load();
            }
        }
    });
})(jQuery);
