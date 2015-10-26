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
     * @param conf
     */
    uploads.bindField = function (element, conf) {
        uploads.bindUpload(conf.key);

        // Setup autocomplete popup.
        var accept = ($(element).find('input[accept]').prop('accept') || '').replace(/\./g, '');

        $('#field-' + conf.key).autocomplete({
            source: bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
            minLength: 2,
            close: function () {
                $('#field-' + conf.key).trigger('change');
            }
        });
    };

    /**
     * Setup upload capability of file lists.
     *
     * @static
     * @function bindFileList
     * @memberof Bolt.uploads
     * @param {string} key
     */
    uploads.bindFileList = function (key) {
        bolt.filelist[key] = new FilelistHolder({id: key, type: 'filelist'});
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
        $('#fileupload-' + key)
            .fileupload({
                dataType: 'json',
                dropZone: $('#dropzone-' + key)
            })
            .on('fileuploaddone', function (evt, data) {
                fileuploadDone(key, data);
            })
            .on('fileuploadprogress', function (evt, data) {
                fileuploadProgress(key, data);
            })
            .on('fileuploadadd', checkFileSize);
    };

    /**
     * Sets up the handlers for the upload list along with drag and drop functionality.
     *
     * @static
     * @function bindUploadList
     * @memberof Bolt.uploads
     * @param {FilelistHolder} list
     * @param {string} key
     * @param {FileModel} FileModel
     */
    uploads.bindUploadList = function (list, key, FileModel) {
        $('#fileupload-' + key)
            .fileupload({
                dataType: 'json',
                dropZone: $(list.idPrefix + list.id),
                pasteZone: null
            })
            .on('fileuploaddone', function (evt, data) {
                $.each(data.result, function (index, file) {
                    var filename = decodeURI(file.url).replace('files/', '');

                    list.add(filename, filename);
                });
            })
            .on('fileuploadadd', checkFileSize)
            .on('fileuploadsubmit', function (e, data) {
                var fileTypes = $('#fileupload-' + key).attr('accept'),
                    pattern,
                    ldata = $(list.idPrefix + key + ' div.list').data('list');

                if (typeof fileTypes !== 'undefined') {
                    pattern = new RegExp('\\.(' + fileTypes.replace(/,/g, '|').replace(/\./g, '') + ')$', 'i');
                    $.each(data.files , function (index, file) {
                        if (!pattern.test(file.name)) {
                            alert(bolt.data(list.datWrongtype, {'%TYPELIST%': ldata.typelist}));
                            e.preventDefault();

                            return false;
                        }

                        var uploadingFile = new FileModel({
                            filename: file.name
                        });
                        file.uploading = uploadingFile;

                        list.uploading.add(uploadingFile);
                    });
                }

                list.render();
            })
            .on('fileuploadprogress', function (evt, data) {
                var progress = data.loaded / data.total;

                $.each(data.files, function (file) {
                    file.uploading.progress = progress;
                    var progressBar = file.uploading.element.find('.progress-bar');
                    progressBar.css('width', Math.round(file.uploading.progress * 100) + '%');
                });
            })
            .on('fileuploadalways', function (evt, data) {
                $.each(data.files, function (file) {
                    list.uploading.remove(file.uploading);
                });
                list.render();
            });
    };

    /**
     * Check if one or more files to upload are larger than allowed.
     *
     * @private
     * @function checkFileSize
     * @memberof Bolt.uploads
     * @param event
     * @param data
     */
    function checkFileSize(event, data) {
        // The jQuery upload doesn't expose an API to cover an entire upload set. So we keep "bad" files
        // in the data.originalFiles, which is the same between multiple files in one upload set.
        var badFiles = [];

        if (typeof data.originalFiles.bad === 'undefined') {
            data.originalFiles.bad = [];
        }

        $.each(data.files, function (file) {
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
    }

    /**
     * Callback for successful upload requests.
     *
     * @private
     * @function fileuploadDone
     * @memberof Bolt.uploads
     *
     * @param {string} key - Key.
     * @param {Object} data - Data.
     */
    function fileuploadDone(key, data) {
        $.each(data.result, function (idx, file) {
            if (file.error) {
                bootbox.alert(
                    '<p>There was an error uploading the file. Make sure the file is not corrupt, ' +
                    'and that the upload-folder is writable.</p>' +
                    '<p>Error message:<br><i>' + file.error + '<i></p>'
                );
            } else {
                $('#field-' + key).val(file.name).trigger('change');

                // Add the uploaded file to our stack.
                bolt.stack.addToStack(file.name);
            }

            // Progress bar
            window.setTimeout(
                function () {
                    $('#progress-' + key).fadeOut('slow');
                },
                file.error ? 50 : 1500
            );
            $('#progress-' + key + ' div.bar').css('width', '100%');
            $('#progress-' + key).removeClass('progress-striped active');
        });
    }

    /**
     * Callback for upload progress events.
     *
     * @private
     * @function fileuploadProgress
     * @memberof Bolt.uploads
     *
     * @param {string} key - Key.
     * @param {Object} data - Data.
     */
    function fileuploadProgress(key, data) {
        var progress = Math.round(100 * data.loaded / data.total);

        $('#progress-' + key).show().addClass('progress-striped active');
        $('#progress-' + key + ' div.progress-bar').css('width', progress + '%');
    }

    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
