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
         * @event jQuery.widget.bolt.buicUpload#buicuploaduploaded
         * @property {string} path - The path to the selected file
         */

        /**
         * The constructor of the upload widget.
         *
         * @private
         */
        _create: function () {
            var fieldset = this.element,
                fileInput = $('input[type=file]', fieldset),
                dropZone = $('.dropzone', fieldset),
                //
                accept = $(fileInput).attr('accept'),
                extensions = accept ? accept.replace(/^\./, '').split(/,\./) : [],
                pattern = new RegExp('(\\.|\\/)(' + extensions.join('|') + ')$', 'i');

            // Set maxSize, if not set on creation.
            if (this.options.maxSize === null) {
                this.options.maxSize = bolt.utils.filterInt(bolt.conf('uploadConfig.maxSize'), 2000000);
            }

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.buicUpload.prototype
             * @private
             *
             * @property {Object} progress - Progress bar widget
             */
            this._ui = {
                progress: $(':bolt-buicProgress', fieldset)
            };

            // Initialize the upload widget.
            fileInput.fileupload({
                dataType: 'json',
                dropZone: dropZone,
                pasteZone: null,
                maxFileSize: this.options.maxSize > 0 ? this.options.maxSize : undefined,
                minFileSize: undefined,
                acceptFileTypes: accept ? pattern : undefined,
                maxNumberOfFiles: undefined,
                messages: {
                    maxFileSize: '>:' + bolt.utils.humanBytes(this.options.maxSize),
                    minFileSize: '<',
                    acceptFileTypes: 'T:.' + extensions.join(', .'),
                    maxNumberOfFiles: '#'
                }
            });

            // Binds event handlers.
            this._on({
                'fileuploadprocessfail': this._onProcessFail,
                'fileuploadsubmit':      this._onUploadSubmit,
                'fileuploadprogress':    this._onUploadProgress,
                'fileuploadalways':      this._onUploadAlways,
                'fileuploaddone':        this._onUploadDone
            });
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
            var self = this;

            $.each(data.files, function () {
                self._ui.progress.buicProgress('add', this.name);
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
            var self = this;

            $.each(data.files, function () {
                self._ui.progress.buicProgress('set', this.name, data.loaded / data.total);
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
            var self = this;

            $.each(data.files, function () {
                self._ui.progress.buicProgress('remove', this.name);
            });
        },

        /**
         * Files successfully uploaded.
         *
         * @private
         * @fires jQuery.widget.bolt.buicUpload#buicuploaduploaded
         *
         * @param {Object} event
         * @param {Object} data
         */
        _onUploadDone: function (event, data) {
            var self = this;

            $.each(data.result, function (idx, file) {
                if (file.error) {
                    bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
                } else {
                    self._trigger('uploaded', event, {path: file.name});
                }
            });
        },

        /**
         * Default options.
         *
         * @property {?number} maxSize - Maximum upload size in bytes. 0 means unlimited.
         */
        options: {
            maxSize: null
        }
    });
})(jQuery, Bolt);
