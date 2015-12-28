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
         * @property {string}        [size]          - Alter the modal size. Allowd values: 'small' and 'large'
         * @property {boolean}       [closer=false]  - Add a close button
         * @property {string}        [classname]     - Add a class to the content part
         * @property {Object|string} [headline]      - Add a headline (If set, the header is build out of it and closer)
         * @property {Object|string} [header]        - Add a header
         * @property {Object|string} [body]          - Add a body
         * @property {Object|string} [footer]        - Add a footer
         * @property {string}        [remote]        - Add a URL to load content from
         * @property {string}        [remote.url]    - Remote: URL to which the request is sent.
         * @property {Object}        [remote.data]   - Remote: data  that is sent to the server with the request.
         * @property {function}      [loaded]        - Callback fired when remote data was laoded
         */
        options: {
            size:      undefined,
            closer:    false,
            classname: undefined,
            headline:  undefined,
            header:    undefined,
            body:      undefined,
            footer:    undefined,
            remote:    undefined,
            loaded:    undefined
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
             * @property {Object} header  - Header element of the modal
             * @property {Object} body    - Body element of the modal
             * @property {Object} footer  - Footer element of the modal
             * @property {Object} content - Content element of the modal
             * @property {Object} dialog  - Dialog element of the modal
             * @property {Object} modal   - The modal
             */
            this._ui = {
                header:  header,
                body:    body,
                footer:  footer,
                content: content,
                dialog:  dialog,
                modal:   modal
            };

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
                    self._fire('show');
                })
                .on('shown.bs.modal', function () {
                    self._fire('shown');
                })
                .on('hide.bs.modal', function () {
                    self._fire('hide');
                })
                .on('hidden.bs.modal', function () {
                    self._fire('hidden');
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
         * Triggers event with modal data added.
         *
         * @private
         * @param {string} eventType - Event type
         */
        _fire: function (eventType) {
            var self = this;

            self._trigger(
                eventType,
                null,
                {
                    close: function () {
                        self._ui.modal.modal('hide');
                    },
                    header:  self._ui.header,
                    body:    self._ui.body,
                    footer:  self._ui.footer
                }
            );
        },

        /**
         * Render.
         */
        _init: function () {
            // Set modals size.
            this._ui.dialog
                .toggleClass('modal-sm', this.options.size === 'small')
                .toggleClass('modal-lg', this.options.size === 'large');

            // Build and add content.
            if (this.options.remote) {
                this._load();
            } else {
                this._update();
            }
        },

        /**
         * Loads content from a url.
         */
        _load: function () {
            var self = this;

            self._ui.content.addClass('modal-loading');

            $.get(self.options.remote.url, self.options.remote.data || {})
                .done(function (data) {
                    self.options.classname = $(data)[0].className;

                    $.each(['header', 'body', 'footer'], function (idx, part) {
                        var element = $(data).children(part.replace('body', 'main'))[0];

                        self.options[part] = element ? element.innerHTML : undefined;
                    });

                    self._update();
                    self._fire('loaded');
                })
                .fail(function () {
                    self.options.header = undefined;
                    self.options.body =
                        '<button type=button class="btn btn-default modal-retry">' +
                            '<i class="fa fa-refresh"></i>' +
                        '</button>';
                    self.options.footer = undefined;

                    self._update();
                })
                .always(function () {
                    self._ui.content.removeClass('modal-loading');
                });
        },

        /**
         * Renders header, body and footer parts of the modal.
         */
        _update: function () {
            var self = this,
                content;

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

            // Set content classname.
            this._ui.content
                .attr('class', 'modal-content')
                .addClass(this.options.classname);

            // Render modal parts.
            $.each(['header', 'body', 'footer'], function (idx, part) {
                content = $('<div>').append(self.options[part]).html() || '';

                self._ui[part]
                    .off()
                    .toggleClass('hidden', !content.length)
                    .html(content);
            });

            this._ui.modal.modal('handleUpdate');
        }
    });
})(jQuery);
