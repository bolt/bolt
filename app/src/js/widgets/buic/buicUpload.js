/**
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * BUIC upload widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicUpload
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicUpload', /** @lends jQuery.widget.bolt.buicUpload.prototype */ {
        /**
         * Event reporting that a file was selected.
         *
         * @event jQuery.widget.bolt.buicUpload#uploaduploaded
         * @property {string} path - The path to the selected file
         */

        /**
         * The constructor of the upload widget.
         *
         * @private
         */
        _create: function () {
            var fileInput = $('input[type=file]', this.element),
                dropZone = $('.dropzone', this.element),
                //
                maxSize = bolt.conf('uploadConfig.maxSize'),
                accept = $(fileInput).attr('accept'),
                extensions = accept ? accept.replace(/^\./, '').split(/,\./) : [],
                pattern = new RegExp('(\\.|\\/)(' + extensions.join('|') + ')$', 'i');

            fileInput
                .fileupload({
                    dataType: 'json',
                    dropZone: dropZone,
                    pasteZone: null,
                    maxFileSize: maxSize > 0 ? maxSize : undefined,
                    minFileSize: undefined,
                    acceptFileTypes: accept ? pattern : undefined,
                    maxNumberOfFiles: undefined,
                    messages: {
                        maxFileSize: '>:' + bolt.utils.humanBytes(maxSize),
                        minFileSize: '<',
                        acceptFileTypes: 'T:.' + extensions.join(', .'),
                        maxNumberOfFiles: '#'
                    }
                })
                .on('fileuploadprocessfail', this._onProcessFail)
                .on('fileuploadsubmit',      this._onUploadSubmit)
                .on('fileuploadprogress',    this._onUploadProgress)
                .on('fileuploadalways',      this._onUploadAlways)
                .on('fileuploaddone',        this._onUploadDone);
        },

        /**
         * Upload processing failed.
         *
         * @private
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onProcessFail: function (event, data) {
            var currentFile = data.files[data.index],
                    type = currentFile.error.substr(0, 1),
                    alert,
                    context = {
                        '%FILENAME%': currentFile.name,
                        '%FILESIZE%': bolt.utils.humanBytes(currentFile.size),
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
        },

        /**
         * Upload starts.
         *
         * @private
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onUploadSubmit: function(event, data) {
            var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

            $.each(data.files, function () {
                progress.buicProgress('add', this.name);
            });
        },

        /**
         * Signal upload progress.
         *
         * @private
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onUploadProgress: function (event, data) {
            var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

            $.each(data.files, function () {
                progress.buicProgress('set', this.name, data.loaded / data.total);
            });
        },

        /**
         * After successful or failed upload.
         *
         * @private
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onUploadAlways: function (event, data) {
            var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

            $.each(data.files, function () {
                progress.buicProgress('remove', this.name);
            });
        },

        /**
         * Files successfully uploaded.
         *
         * @private
         * @fires jQuery.widget.bolt.buicUpload#uploaduploaded
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onUploadDone: function (event, data) {
            var fieldset = $(event.target).closest('fieldset');

            $.each(data.result, function (idx, file) {
                if (file.error) {
                    bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
                } else {
                    fieldset.trigger('uploaduploaded', {path: file.name});
                }
            });
        }
    });
})(jQuery, Bolt);
