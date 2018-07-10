/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Embed field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldEmbed
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldEmbed', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldEmbed.prototype */ {
        /**
         * The constructor of the video field widget.
         *
         * @private
         */
        _create: function () {
            var self = this,
                fieldset = this.element,
                timeout = 0;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} url            - Input field of video url
             * @property {Object} width          - Input field of video width
             * @property {Object} height         - Input field of video height
             * @property {Object} html           - Hidden field holding the iframe to embed
             * @property {Object} ratio          - Hidden field holding the video aspect ratio
             * @property {Object} authorName     - Hidden field holding the author name
             * @property {Object} authorUrl      - Hidden field holding the author url
             * @property {Object} provider_name  - Hidden field holding the provider name
             * @property {Object} thumbnailUrl   - Hidden field holding the thumbnail link
             * @property {Object} preview        - The thumbnail image
             * @property {Object} play           - Play button
             * @property {Object} refresh        - Refresh button
             * @property {Object} spinner        - Spinner
             */
            this._ui = {
                url:            fieldset.find('input.url'),
                width:          fieldset.find('input.width'),
                height:         fieldset.find('input.height'),
                html:           fieldset.find('input.html'),
                ratio:          fieldset.find('input.ratio'),
                authorName:     fieldset.find('input.author_name'),
                authorUrl:      fieldset.find('input.author_url'),
                provider_name:  fieldset.find('input.provider_name'),
                thumbnailUrl:   fieldset.find('input.thumbnail_url'),
                preview:        fieldset.find('img'),
                play:           fieldset.find('button.preview'),
                refresh:        fieldset.find('button.refresh'),
                spinner:        fieldset.find('button.refresh i')
            };

            self._ui.url.on('propertychange input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(
                    function () {
                        self._update();
                    },
                    1000
                );
            });

            self._ui.width.on('propertychange input', function () {
                if (self._ui.ratio.val() > 0) {
                    self._ui.height.val(Math.round(self._ui.width.val() / self._ui.ratio.val()));
                }
            });

            self._ui.height.on('propertychange input', function () {
                if (self._ui.ratio.val() > 0) {
                    self._ui.width.val(Math.round(self._ui.height.val() * self._ui.ratio.val()));
                }
            });

            self._ui.refresh.on('click', function () {
                self._update();
            });

            self._ui.play.on('click', function () {
                $('body').buicModal({
                    size:     'large',
                    closer:   true,
                    headline: self._ui.provider_name.val(),
                    body:     self._ui.html.val()
                });
            });
        },

        /**
         * Gets embedding info from the internal embed API and then updates fields.
         *
         * @private
         */
        _update: function () {
            var self = this,
                url = bolt.data('endpoint.embed'),
                form = $('#id').closest('form'),
                token = form.find('input[name="content_edit[_token]"]').val(),
                request = {
                    format:   'json',
                    url:      self._ui.url.val(),
                    _token:   token,
                    provider: 'oembed'
                };

            // If val is emptied, clear the fields.
            if (request.url.length < 2) {
                self._set({});
            } else {
                this._ui.refresh.prop('disabled', true);
                self._ui.spinner.addClass('fa-spin');

                $.post(url, request)
                    .done(function (data) {
                        self._set(data);
                    })
                    .fail(function () {
                        self._set({});
                    })
                    .always(function () {
                        self._ui.spinner.removeClass('fa-spin');
                    });
            }
        },

        /**
         * Sets data fields, display fields, preview and thumbnail.
         *
         * @private
         * @param {Object} data
         */
        _set: function (data) {
            this._ui.html.val(data.html || '');
            this._ui.width.val(data.width || '');
            this._ui.height.val(data.height || '');
            this._ui.authorName.val(data.author_name || '');
            this._ui.authorUrl.val(data.author_url || '');
            this._ui.provider_name.val(data.provider_name || '');
            this._ui.thumbnailUrl.val(data.thumbnail_url || this._ui.preview.data('defaultUrl'));
            this._ui.refresh.prop('disabled', this._ui.url.val().length <= 2 || this._ui.html.val().length > 0);
        }
    });
})(jQuery, Bolt);
