/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Video field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldVideo
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldVideo', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldVideo.prototype */ {
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
             * @property {Object} html           - Hidden field holding the iframe to embed the video
             * @property {Object} ratio          - Hidden field holding the video aspect ratio
             * @property {Object} authorName     - Hidden field holding the author name
             * @property {Object} authorUrl      - Hidden field holding the author url
             * @property {Object} title          - Hidden field holding the video title
             * @property {Object} thumbnailUrl   - Hidden field holding the video thumbnail link
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
                authorName:     fieldset.find('input.authorname'),
                authorUrl:      fieldset.find('input.authorurl'),
                title:          fieldset.find('input.title'),
                thumbnailUrl:   fieldset.find('input.thumbnailurl'),
                preview:        fieldset.find('img'),
                play:           fieldset.find('.imageholder button'),
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
                    headline: self._ui.title.val(),
                    body:     self._ui.html.val()
                });
            });
        },

        /**
         * Gets video embedding info from http://api.embed.ly and then updates video fields.
         *
         * @private
         */
        _update: function () {
            var self = this,
                url = 'https://api.embed.ly/1/oembed',
                request = {
                    format: 'json',
                    key:    '51fa004148ad4d05b115940be9dd3c7e',
                    url:    self._ui.url.val()
                };

            // If val is emptied, clear the video fields.
            if (request.url.length < 2) {
                self._set({});
            } else {
                this._ui.refresh.prop('disabled', true);
                self._ui.spinner.addClass('fa-spin');

                $.getJSON(url, request)
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
         * @param {Object} data - Date sent from embed.ly
         */
        _set: function (data) {
            var thumbnailUrl = data.thumbnail_url || bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';

            this._ui.html.val(data.html || '');
            this._ui.width.val(data.width || '');
            this._ui.height.val(data.height || '');
            this._ui.ratio.val(data.width && data.height ? data.width / data.height : '');
            this._ui.authorName.val(data.author_name || '');
            this._ui.authorUrl.val(data.author_url || '');
            this._ui.title.val(data.title || '');
            this._ui.thumbnailUrl.val(data.thumbnail_url || '');
            this._ui.preview.attr('src', thumbnailUrl);
            if (data.html) {
                this._ui.play.removeClass('hidden');
            } else {
                this._ui.play.addClass('hidden');
            }
            this._ui.refresh.prop('disabled', this._ui.url.val().length <= 2 || this._ui.html.val().length > 0);
        }
    });
})(jQuery, Bolt);
