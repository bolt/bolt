/**
 * Module to handle upload functionality.
 *
 * @mixin
 * @namespace Bolt.uploads
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.uploads mixin container.
     *
     * @private
     * @type {Object}
     */
    var uploads = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.uploads
     */
    uploads.init = function () {

    };

    /**
     * Initializes the mixin.
     *
     * @static
     * @function update
     * @memberof Bolt.uploads
     * @param element
     */
    uploads.bind = function (element) {
        $('input[data-upload]', element).each(function () {
            var data = $(this).data('upload');
            var accept = $(this).attr('accept');
            accept = accept ? accept.replace(/\./g, '') : '';
            var autocomplete_conf;
            switch (data.type) {
                case 'Image':
                case 'File':
                    bindFileUpload(data.key);

                    autocomplete_conf = {
                        source: Bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
                        minLength: 2
                    };
                    if (data.type === 'Image') {
                        autocomplete_conf.close = function () {
                            var path = $('#field-' + data.key).val(),
                                url;

                            if (path) {
                                url = Bolt.conf('paths.root') +'thumbs/' + data.width + 'x' + data.height + 'c/' +
                                    encodeURI(path);
                            } else {
                                url = Bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';
                            }
                            $('#thumbnail-' + data.key).html(
                                '<img src="'+ url + '" width="' + data.width + '" height="' + data.height + '">'
                            );
                        };
                    }
                    $('#field-' + data.key).autocomplete(autocomplete_conf);
                    break;

                case 'ImageList':
                    Bolt.imagelist[data.key] = new FilelistHolder({id: data.key, type: data.type});
                    break;

                case 'FileList':
                    Bolt.filelist[data.key] = new FilelistHolder({id: data.key, type: data.type});
                    break;
            }
        });
    };


    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
