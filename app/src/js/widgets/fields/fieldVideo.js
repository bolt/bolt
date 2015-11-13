/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Video field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldVideo
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldVideo', /** @lends jQuery.widget.bolt.fieldVideo.prototype */ {
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
             * @property {Object} url  -
             * @property {Object} html   -
             * @property {Object} width -
             * @property {Object} height   -
             * @property {Object} ratio   -
             * @property {Object} text -
             * @property {Object} modalBody   -
             * @property {Object} authorName   -
             * @property {Object} authorUrl   -
             * @property {Object} title   -
             * @property {Object} thumbContainer   -
             * @property {Object} thumbnail   -
             */
            this._ui = {
                url:            fieldset.find('[data-video="url"]'),
                html:           fieldset.find('[data-video="html"]'),
                width:          fieldset.find('[data-video="width"]'),
                height:         fieldset.find('[data-video="height"]'),
                ratio:          fieldset.find('[data-video="ratio"]'),
                text:           fieldset.find('p.matched-video'),
                modalBody:      fieldset.find('[data-video="modal"] .modal-body'),
                authorName:     fieldset.find('[data-video="authorname"]'),
                authorUrl:      fieldset.find('[data-video="authorurl"]'),
                title:          fieldset.find('[data-video="title"]'),
                thumbContainer: fieldset.find('[data-video="thumbcontainer"]'),
                thumbnail:      fieldset.find('[data-video="thumbnail"]')
            };

            self._ui.url.on('propertychange input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(
                    function () {
                        self._update();
                    },
                    400
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
                $.getJSON(url, request)
                    .done(function (data) {
                        self._set(data);
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
            this._ui.html.val(data.html || '');
            this._ui.width.val(data.width || '');
            this._ui.height.val(data.height || '');
            this._ui.ratio.val(data.width && data.height ? data.width / data.height : '');
            this._ui.text.html('"<b>' + (data.title || '—') + '</b>" by ' + (data.author_name || '—'));
            this._ui.modalBody.html(data.html || '');
            this._ui.authorName.val(data.author_name || '');
            this._ui.authorUrl.val(data.author_url || '');
            this._ui.title.val(data.title || '');
            // Thumbnail
            if (data.thumbnail_url) {
                this._ui.thumbContainer.html('<img src="' + data.thumbnail_url + '" width="200" height="150">');
                this._ui.thumbnail.val(data.thumbnail_url);
            } else {
                this._ui.thumbContainer.html('');
                this._ui.thumbnail.val('');
            }
        }
    });
})(jQuery);
