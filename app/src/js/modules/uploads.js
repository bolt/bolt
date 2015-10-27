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
     * @param {Object} element
     * @param {Object} conf
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
     * @param {string} key
     */
    uploads.bindUpload = function (key) {
        $('#fileupload-' + key)
            .fileupload(
                uploadOptions(key, '#dropzone-' + key)
            )
            .on('fileuploaddone', function (evt, data) {
                fileuploadDone(key, data);
            })
            .on('fileuploadprogress', function (evt, data) {
                fileuploadProgress(key, data);
            })
            .on('fileuploadprocessfail', function (evt, data) {
                fileuploadProcessFail(key, data);
            });
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
            .fileupload(
                uploadOptions(key, list.idPrefix + list.id)
            )
            .on('fileuploaddone', function (evt, data) {
                list.uploadDone(data.result);
            })
            .on('fileuploadprocessfail', function (evt, data) {
                fileuploadProcessFail(key, data);
            })
            .on('fileuploadsubmit', function (e, data) {
                $.each(data.files , function (idx, file) {
                    file.uploading = new FileModel({
                        filename: file.name
                    });
                    list.uploading.add(file.uploading);
                });

                list.render();
            })
            .on('fileuploadprogress', function (evt, data) {
                var progress = data.loaded / data.total;

                $.each(data.files, function (idx, file) {
                    file.uploading.progress = progress;
                    var progressBar = file.uploading.element.find('.progress-bar');
                    progressBar.css('width', Math.round(file.uploading.progress * 100) + '%');
                });
            })
            .on('fileuploadalways', function (evt, data) {
                $.each(data.files, function (idx, file) {
                    list.uploading.remove(file.uploading);
                });
                list.render();
            });
    };

    /**
     * Returns an upload option object.
     *
     * @private
     * @function uploadOptions
     * @memberof Bolt.uploads
     * @param {string} key
     * @param {string} dropzone
     * @returns {Object}
     */
    function uploadOptions(key, dropzone) {
        var maxSize = bolt.conf('uploadConfig.maxSize'),
            accept = $('#fileupload-' + key).attr('accept'),
            extensions = accept ? accept.replace(/^\./, '').split(/,\./) : [],
            pattern = new RegExp('(\\.|\\/)(' + extensions.join('|') + ')$', 'i');

        return {
            dataType: 'json',
            dropZone: $(dropzone),
            pasteZone: null,
            maxFileSize: maxSize > 0 ? maxSize : undefined,
            minFileSize: undefined,
            acceptFileTypes: accept ? pattern : undefined,
            maxNumberOfFiles: undefined,
            messages: {
                maxFileSize: '>:' + maxSize,
                minFileSize: '<',
                acceptFileTypes: 'T:.' + extensions.join(', .'),
                maxNumberOfFiles: '#'
            }
        };
    }

    /**
     * Upload processing failed.
     *
     * @private
     * @function fileuploadProcessFail
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function fileuploadProcessFail(event, data) {
        var currentFile = data.files[data.index],
                type = currentFile.error.substr(0, 1),
                alert,
                context = {
                    '%FILENAME%': currentFile.name,
                    '%FILESIZE%': currentFile.size,
                    '%FILETYPE%': currentFile.type,
                    '%ALLOWED%': currentFile.error.substr(2)
                };

        switch (type) {
            case '>':
                alert = bolt.data('field.uploads.template.large-file', context);
                break;

            case 'T':
                alert = bolt.data('field.uploads.template.wrong-type', context);
                break;

            default:
                alert = '<p>' + currentFile.error + '</p>';
        }
        bootbox.alert(alert);
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
                bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
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
