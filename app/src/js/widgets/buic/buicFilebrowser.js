/**
 * @param {Object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * BUIC filebrowser widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicFilebrowser
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicFilebrowser', /** @lends jQuery.widget.bolt.buicFilebrowser.prototype */ {
        /**
         * Default options.
         *
         * @property {string} [url] - URL to browse
         */
        options: {
            url: ''
        },

        /**
         * The constructor of the filebrowser widget.
         *
         * @private
         */
        _create: function () {
            this._on({
                'click': function() {
                    this._browse();
                }
            });
        },

        /**
         *
         *
         * @private
         */
        _browse: function () {
            var self = this,
                data = {};

            $('body').buicModal({
                size: 'large',
                remote: {
                    url:  self.options.url,
                    data: data
                },
                loaded: function (evt, data) {
                    data.body
                        .on('click.bolt', '[data-fbrowser-chdir]', function () {
                        self.options.url = $(this).data('fbrowser-chdir');
                        self._browse();
                    });
                }
            });
        }
    });
})(jQuery);
