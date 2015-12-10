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
     * Adds a file to an upload list.
     *
     * @static
     * @function addToList
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     * @param {string} filename
     * @param {string=} title (Optional)
     */
    uploads.addToList = function (fieldset, filename, title) {
        var listField = $('div.list', fieldset),
            type = $(fieldset).data('bolt-fieldset'),
            templateItem = type === 'filelist' ? 'field.filelist.template.item' : 'field.imagelist.template.item';

        // Remove empty list message, if there.
        $('>p', listField).remove();

        // Append to list.
        listField.append(
            $(Bolt.data(
                templateItem,
                {
                    '%TITLE_A%':    title || filename,
                    '%FILENAME_E%': $('<div>').text(filename).html(), // Escaped
                    '%FILENAME_A%': filename
                }
            ))
        );

        serializeList(fieldset);
    };

    /**
     * This function works at a lower level than the bindField function, it sets up the handlers for the upload
     * button along with drag and drop functionality. To do this it uses the `key` parameter which needs to
     * be a unique ID.
     *
     * @static
     * @function bindUpload
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    uploads.bindUpload = function (fieldset) {
        var fileInput = $(fieldset).find('input[type=file]'),
            dropZone = $(fieldset).find('.dropzone'),
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
            .on('fileuploadprocessfail', onProcessFail)
            .on('fileuploadsubmit', onUploadSubmit)
            .on('fileuploadprogress', onUploadProgress)
            .on('fileuploadalways', onUploadAlways)
            .on('fileuploaddone', onUploadDone);
    };

    /**
     * Binds event to select from stack button.
     *
     * @static
     * @function bindSelectFromStack
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    uploads.bindSelectFromStack = function (fieldset) {
        $('ul.select-from-stack a', fieldset).on('click', function () {
            var path = $(this).data('path');

            // Close the dropdown.
            $(this).closest('.btn-group').find('button.dropdown-toggle').dropdown('toggle');

            add(fieldset, path, false);

            return false;
        });
    };

    /**
     * Upload processing failed.
     *
     * @private
     * @function onProcessFail
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function onProcessFail(event, data) {
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
    }

    /**
     * Upload starts.
     *
     * @private
     * @function onUploadSubmit
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function onUploadSubmit(event, data) {
        var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

        $.each(data.files, function () {
            progress.buicProgress('add', this.name);
        });
    }

    /**
     * Signal upload progress.
     *
     * @private
     * @function onUploadProgress
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function onUploadProgress(event, data) {
        var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

        $.each(data.files, function () {
            progress.buicProgress('set', this.name, data.loaded / data.total);
        });
    }

    /**
     * After successful or failed upload.
     *
     * @private
     * @function onUploadAlways
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function onUploadAlways(event, data) {
        var progress = $(event.target).closest('fieldset').find(':bolt-buicProgress');

        $.each(data.files, function () {
            progress.buicProgress('remove', this.name);
        });
    }

    /**
     * Files successfully uploaded.
     *
     * @private
     * @function onUploadDone
     * @memberof Bolt.uploads
     * @param {Object} event
     * @param {Object} data
     */
    function onUploadDone(event, data) {
        var fieldset = $(event.target).closest('fieldset');

        $.each(data.result, function (idx, file) {
            if (file.error) {
                bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
            } else {
                add(fieldset, file.name, true);
            }
        });
    }

    /**
     * Serialize list data on change.
     *
     * @private
     * @function serializeList
     * @memberof Bolt.uploads
     *
     * @param {Object} fieldset
     */
    function serializeList(fieldset) {
        var listField = $('div.list', fieldset),
            dataField = $('textarea', fieldset),
            isFile = $(fieldset).data('bolt-fieldset') === 'filelist',
            templateEmpty = isFile ? 'field.filelist.template.empty' : 'field.imagelist.template.empty',
            data = [];

        $('.item', listField).each(function () {
            data.push({
                filename: $(this).find('input.filename').val(),
                title: $(this).find('input.title').val()
            });
        });
        dataField.val(JSON.stringify(data));

        // Display empty list message.
        if (data.length === 0) {
            listField.html(Bolt.data(templateEmpty));
        }
    }

    /**
     * Add the file.
     *
     * @private
     * @function add
     * @memberof Bolt.uploads
     *
     * @param {Object} fieldset
     * @param {string} path
     * @param {boolean} stackAdd
     */
    function add(fieldset, path, stackAdd) {
        if (fieldset.is(':bolt-fieldFile')) {
            fieldset.fieldFile('setPath', path, stackAdd);
        } else if (fieldset.is(':bolt-fieldImage')) {
            fieldset.fieldImage('setPath', path, stackAdd);
        } else if (fieldset.is(':bolt-fieldFilelist') || fieldset.is(':bolt-fieldImagelist')) {
            uploads.addToList(fieldset, path);
        } else if (fieldset.is(':bolt-buicStack')) {
            fieldset.buicStack('add', path);
        }
    }

    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
