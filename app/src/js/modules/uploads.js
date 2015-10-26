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
     * This function handles the setup of any field that requires upload capability.
     *
     * @static
     * @function bindField
     * @memberof Bolt.uploads
     * @param element
     */
    uploads.bindField = function (element) {
        var type = $(element).data('bolt-field');

        $('input[data-upload]', element).each(function () {
            var data = $(this).data('upload'),
                accept = $(this).attr('accept'),
                autocompleteConf;

            accept = accept ? accept.replace(/\./g, '') : '';

            switch (type) {
                case 'image':
                case 'file':
                    uploads.bindUpload(data.key);

                    autocompleteConf = {
                        source: bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
                        minLength: 2
                    };
                    if (type === 'image') {
                        autocompleteConf.close = function () {
                            var path = $('#field-' + data.key).val(),
                                url;

                            if (path) {
                                url = bolt.conf('paths.root') +'thumbs/' + data.width + 'x' + data.height + 'c/' +
                                    encodeURI(path);
                            } else {
                                url = bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';
                            }
                            $('#thumbnail-' + data.key).html(
                                '<img src="'+ url + '" width="' + data.width + '" height="' + data.height + '">'
                            );
                        };
                    }
                    $('#field-' + data.key).autocomplete(autocompleteConf);
                    break;

                case 'filelist':
                    bolt.filelist[data.key] = new FilelistHolder({id: data.key, type: type});
                    break;
            }
        });
    };

    /**
     * Setup upload capability of image lists.
     *
     * @static
     * @function bindImageList
     * @memberof Bolt.uploads
     * @param {string} key
     */
    uploads.bindImageList = function (key) {
        bolt.imagelist[key] = new FilelistHolder({id: key, type: 'imagelist'});
    };

    /**
     * This function works at a lower level than the bindField function, it sets up the handlers for the upload
     * button along with drag and drop functionality. To do this it uses the `key` parameter which needs to
     * be a unique ID.
     *
     * @static
     * @function bindUpload
     * @memberof Bolt.uploads
     * @param key
     */
    uploads.bindUpload = function (key) {
        // Since jQuery File Upload's 'paramName' option seems to be ignored,
        // it requires the name of the upload input to be "images[]". Which clashes
        // with the non-fancy fallback, so we hackishly set it here. :-/
        $('#fileupload-' + key)
            .fileupload({
                dataType: 'json',
                dropZone: $('#dropzone-' + key),
                done: function (e, data) {
                    $.each(data.result, function (index, file) {
                        var filename,
                            message;

                        if (file.error === undefined) {
                            filename = decodeURI(file.url).replace('files/', '');
                            $('#field-' + key).val(filename);
                            $('#thumbnail-' + key).html(
                                '<img src="' + bolt.conf('paths.root') + 'thumbs/200x150c/' + encodeURI(filename) +
                                '" width="200" height="150">'
                            );
                            window.setTimeout(
                                function () {
                                    $('#progress-' + key).fadeOut('slow');
                                },
                                1500
                            );

                            // Add the uploaded file to our stack.
                            bolt.stack.addToStack(filename);

                        } else {
                            message = 'Oops! There was an error uploading the file. Make sure the file is not ' +
                                "corrupt, and that the 'files/'-folder is writable." +
                                "\n\n(error was: " + file.error + ')';

                            alert(message);
                            window.setTimeout(
                                function () {
                                    $('#progress-' + key).fadeOut('slow');
                                },
                                50
                            );
                        }
                        $('#progress-' + key + ' div.bar').css('width', '100%');
                        $('#progress-' + key).removeClass('progress-striped active');
                    });
                },
                add: uploads.checkFileSize
            })
            .bind('fileuploadprogress', function (e, data) {
                var progress = Math.round(100 * data.loaded / data.total);

                $('#progress-' + key).show().addClass('progress-striped active');
                $('#progress-' + key + ' div.progress-bar').css('width', progress + '%');
            });
    };

    /**
     * This function works at a lower level than the bindField function, it sets up the handlers for the upload
     * button along with drag and drop functionality. To do this it uses the `key` parameter which needs to
     * be a unique ID.
     *
     * @static
     * @function checkFileSize
     * @memberof Bolt.uploads
     * @param event
     * @param data
     */
    uploads.checkFileSize = function (event, data) {
        // The jQuery upload doesn't expose an API to cover an entire upload set. So we keep "bad" files
        // in the data.originalFiles, which is the same between multiple files in one upload set.
        var badFiles = [];

        if (typeof data.originalFiles.bad === 'undefined') {
            data.originalFiles.bad = [];
        }

        _.each(data.files, function (file) {
            if ((file.size || 0) > bolt.conf('uploadConfig.maxSize') && bolt.conf('uploadConfig.maxSize') > 0) {
                badFiles.push(file.name);
                data.originalFiles.bad.push(file.name);
            }
        });

        if (data.originalFiles.bad.length > 0) {
            var filename1 = data.files[data.files.length - 1].name;
            var filename2 = data.originalFiles[data.originalFiles.length - 1].name;

            if (filename1 === filename2) {
                // We're at the end of this upload cycle
                var message = 'One or more of the files that you selected was larger than the max size of ' +
                    bolt.conf('uploadConfig.maxSizeNice') + ":\n\n" +
                    data.originalFiles.bad.join("\n");

                alert(message);
            }
        }

        if (badFiles.length === 0) {
            data.submit();
        }
    };

    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
