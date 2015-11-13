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

            fieldset.find('[data-video="main"]').bind(
                'propertychange input',
                function () {
                    clearTimeout(timeout);
                    timeout = setTimeout(
                        function () {
                            self._update();
                        },
                        400
                    );
                }
            );

            fieldset.find('[data-video="width"]').bind(
                'propertychange input',
                function () {
                    if (fieldset.find('[data-video="ratio"]').val() > 0) {
                        fieldset.find('[data-video="height"]').val(
                            Math.round(
                                fieldset.find('[data-video="width"]').val() /
                                fieldset.find('[data-video="ratio"]').val()
                            )
                        );
                    }
                }
            );

            fieldset.find('[data-video="height"]').bind(
                'propertychange input',
                function () {
                    if (fieldset.find('[data-video="ratio"]').val() > 0) {
                        fieldset.find('[data-video="width"]').val(
                            Math.round(
                                fieldset.find('[data-video="height"]').val() *
                                fieldset.find('[data-video="ratio"]').val()
                            )
                        );
                    }
                }
            );
        },

        /**
         * Gets video embedding info from http://api.embed.ly and then updates video fields
         *
         * @private
         */
        _update: function () {
            var fieldset = this.element;
            // Embed endpoint https://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
            var endpoint = 'https://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=',
                val = fieldset.find('[data-video="main"]').val(),
                url = endpoint + encodeURI(val);

            // If val is emptied, clear the video fields.
            if (val.length < 2) {
                fieldset.find('[data-video="html"]').val('');
                fieldset.find('[data-video="width"]').val('');
                fieldset.find('[data-video="height"]').val('');
                fieldset.find('[data-video="ratio"]').val('');
                fieldset.find('[data-video="text"]').html('');
                fieldset.find('[data-video="modal"]').find('.modal-body').html('');
                fieldset.find('[data-video="authorname"]').val('');
                fieldset.find('[data-video="authorurl"]').val('');
                fieldset.find('[data-video="title"]').val('');
                fieldset.find('[data-video="thumbcontainer"]').html('');
                fieldset.find('[data-video="thumbnail"]').val('');
                return;
            }

            $.getJSON(url, function (data) {
                if (data.html) {
                    fieldset.find('[data-video="html"]').val(data.html);
                    fieldset.find('[data-video="width"]').val(data.width);
                    fieldset.find('[data-video="height"]').val(data.height);
                    fieldset.find('[data-video="ratio"]').val(data.width / data.height);
                    fieldset.find('[data-video="text"]').html('"<b>' + data.title + '</b>" by ' + data.author_name);
                    fieldset.find('[data-video="modal"]').find('.modal-body').html(data.html);
                    fieldset.find('[data-video="authorname"]').val(data.author_name);
                    fieldset.find('[data-video="authorurl"]').val(data.author_url);
                    fieldset.find('[data-video="title"]').val(data.title);
                }

                if (data.thumbnail_url) {
                    fieldset.find('[data-video="thumbcontainer"]').html(
                        '<img src="' + data.thumbnail_url + '" width="200" height="150">'
                    );
                    fieldset.find('[data-video="thumnail"]').val(data.thumbnail_url);
                }
            });
        }
    });
})(jQuery);
