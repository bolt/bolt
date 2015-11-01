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
     * @param {Object} fieldset
     */
    uploads.bindField = function (fieldset) {
        uploads.bindUpload(fieldset.id);

        // Setup autocomplete popup.
        var accept = ($(fieldset).find('input[accept]').prop('accept') || '').replace(/\./g, ''),
            input = $(fieldset).find('input.path');

        input.autocomplete({
            source: bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
            minLength: 2,
            close: function () {
                $(input).trigger('change');
            }
        });
    };

    /**
     * Setup upload capability of file lists.
     *
     * @static
     * @function bindFileList
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    uploads.bindFileList = function (fieldset) {
        bolt.filelist[fieldset.id] = new FilelistHolder({fieldset: fieldset, type: 'filelist'});
        uploads.bindUpload(fieldset.id, bolt.filelist[fieldset.id]);
    };

    /**
     * Setup upload capability of image lists.
     *
     * @static
     * @function bindImageList
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    uploads.bindImageList = function (fieldset) {
        bolt.imagelist[fieldset.id] = new FilelistHolder({fieldset: fieldset, type: 'imagelist'});
        uploads.bindUpload(fieldset.id, bolt.imagelist[fieldset.id]);
    };

    /**
     * This function works at a lower level than the bindField function, it sets up the handlers for the upload
     * button along with drag and drop functionality. To do this it uses the `key` parameter which needs to
     * be a unique ID.
     *
     * @static
     * @function bindUpload
     * @memberof Bolt.uploads
     * @param {string} fieldId
     * @param {FilelistHolder} list
     */
    uploads.bindUpload = function (fieldId, list) {
        var fieldset = $('#' + fieldId),
            progress = $(fieldset).find('.buic-progress'),
            dropzone = $(fieldset).find('.dropzone'),
            fileinput = $(fieldset).find('input[type=file]'),
            pathinput = $(fieldset).find('input.path'),
            //
            maxSize = bolt.conf('uploadConfig.maxSize'),
            accept = $(fileinput).attr('accept'),
            extensions = accept ? accept.replace(/^\./, '').split(/,\./) : [],
            pattern = new RegExp('(\\.|\\/)(' + extensions.join('|') + ')$', 'i'),
            uploadOptions = {
                dataType: 'json',
                dropZone: $(dropzone),
                pasteZone: null,
                maxFileSize: maxSize > 0 ? maxSize : undefined,
                minFileSize: undefined,
                acceptFileTypes: accept ? pattern : undefined,
                maxNumberOfFiles: undefined,
                messages: {
                    maxFileSize: '>:' + humanBytes(maxSize),
                    minFileSize: '<',
                    acceptFileTypes: 'T:.' + extensions.join(', .'),
                    maxNumberOfFiles: '#'
                }
            };

        fileinput
            .fileupload(
                uploadOptions
            )
            .on('fileuploaddone', function (evt, data) {
                if (list) {
                    $.each(data.result, function (idx, file) {
                        list.addToList(file.name, file.name);
                    });
                } else {
                    fileuploadDone(pathinput, data);
                }
            })
            .on('fileuploadprocessfail', function (evt, data) {
                fileuploadProcessFail(evt, data);
            })
            .on('fileuploadsubmit', function (evt, data) {
                $.each(data.files, function () {
                    $(progress).trigger('buic:progress-add', [this.name]);
                });
            })
            .on('fileuploadalways', function (evt, data) {
                $.each(data.files, function () {
                    $(progress).trigger('buic:progress-remove', [this.name]);
                });
            })
            .on('fileuploadprogress', function (evt, data) {
                $.each(data.files, function () {
                    $(progress).trigger('buic:progress-set', [this.name, data.loaded / data.total]);
                });
            });
    };

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
                    '%FILESIZE%': humanBytes(currentFile.size),
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
     * Human readable formatted bytes.
     *
     * @private
     * @function humanBytes
     * @memberof Bolt.uploads
     *
     * @param {integer} val - Value to format.
     */
    function humanBytes(val) {
        var units = ' kMGTPEZY',
            u = -1;

        while (++u < 8 && Math.abs(val) >= 1000) {
            val /= 1000;
        }

        if (!!(typeof Intl === 'object' && Intl && typeof Intl.NumberFormat === 'function')) {
            val = val.toLocaleString(
                bolt.conf('locale.long').replace(/_/g, '-'),
                {maximumSignificantDigits: 3}
            );
        } else {
            val = val.toFixed(2);
        }

        return val + ' ' + units[u].trim() + 'B';
    }

    /**
     * Callback for successful upload requests.
     *
     * @private
     * @function fileuploadDone
     * @memberof Bolt.uploads
     *
     * @param {Object} pathinput - Path input (single upload only).
     * @param {Object} data - Data.
     */
    function fileuploadDone(pathinput, data) {
        $.each(data.result, function (idx, file) {
            if (file.error) {
                bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
            } else {
                pathinput.val(file.name).trigger('change');

                // Add the uploaded file to our stack.
                bolt.stack.addToStack(file.name);
            }
        });
    }

    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
