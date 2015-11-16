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
        bindUpload(fieldset, false);
        bindSelectFromStack(fieldset);

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
     * This function handles the setup of any list fields that requires upload capability.
     *
     * @static
     * @function bindList
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     * @param {Object} messages
     */
    uploads.bindList = function (fieldset, messages) {
        var lastClick = null;

        $('div.list', fieldset)
            .sortable({
                helper: function (evt, item) {
                    if (!item.hasClass('selected')) {
                        item.toggleClass('selected');
                    }

                    return $('<div></div>');
                },
                start: function (evt, ui) {
                    var elements = $(fieldset).find('.selected').not('.ui-sortable-placeholder'),
                        len = elements.length,
                        currentOuterHeight = ui.placeholder.outerHeight(true),
                        currentInnerHeight = ui.placeholder.height(),
                        margin = parseInt(ui.placeholder.css('margin-top')) +
                            parseInt(ui.placeholder.css('margin-bottom'));

                    elements.hide();
                    ui.placeholder.height(currentInnerHeight + len * currentOuterHeight - currentOuterHeight - margin);
                    ui.item.data('items', elements);
                },
                beforeStop: function (evt, ui) {
                    ui.item.before(ui.item.data('items'));
                },
                stop: function () {
                    $(fieldset).find('.selected').show();
                    serializeList(fieldset);
                },
                delay: 100,
                distance: 5
            })
            .on('click', '.list-item', function (evt) {
                if ($(evt.target).hasClass('list-item')) {
                    if (evt.shiftKey) {
                        if (lastClick) {
                            var currentIndex = $(this).index(),
                                lastIndex = lastClick.index();

                            if (lastIndex > currentIndex) {
                                $(this).nextUntil(lastClick).add(this).add(lastClick).addClass('selected');
                            } else if (lastIndex < currentIndex) {
                                $(this).prevUntil(lastClick).add(this).add(lastClick).addClass('selected');
                            } else {
                                $(this).toggleClass('selected');
                            }
                        }
                    } else if (evt.ctrlKey || evt.metaKey) {
                        $(this).toggleClass('selected');
                    } else {
                        $(fieldset).find('.list-item').not($(this)).removeClass('selected');
                        $(this).toggleClass('selected');
                    }

                    lastClick = evt.shiftKey || evt.ctrlKey || evt.metaKey || $(this).hasClass('selected') ?
                        $(this) : null;
                }
            })
            .on('click', '.remove-button', function (evt) {
                evt.preventDefault();

                if (confirm(messages.removeSingle)) {
                    $(this).closest('.list-item').remove();
                    serializeList(fieldset);
                }
            })
            .on('change', 'input', function () {
                serializeList(fieldset);
            });

        $(fieldset).find('.remove-selected-button').on('click', function () {
            if (confirm(messages.removeMulti)) {
                $(fieldset).find('.selected').closest('.list-item').remove();
                serializeList(fieldset);
            }
        });

        bindUpload(fieldset, true);
        bindSelectFromStack(fieldset);
    };

    /**
     * Bind upload capability to the stack.
     *
     * @static
     * @function bindStack
     * @memberof Bolt.uploads
     * @param {Object} container
     */
    uploads.bindStack = function (container) {
        bindUpload(container);
    };

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
     * @private
     * @function bindUpload
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    function bindUpload(fieldset) {
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
                    maxFileSize: '>:' + humanBytes(maxSize),
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
    }

    /**
     * Binds event to select from stack button.
     *
     * @private
     * @function bindUpload
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     */
    function bindSelectFromStack(fieldset) {
        console.log(fieldset);
        $('ul.select-from-stack a', fieldset).on('click', function () {
            var path = $(this).data('path');

            // Close the dropdown.
            $(this).closest('.btn-group').find('button.dropdown-toggle').dropdown('toggle');

            switch ($(fieldset).data('bolt-fieldset')) {
                case 'file':
                case 'image':
                    $('input.path', fieldset).val(path).trigger('change');
                    break;
                case 'filelist':
                    uploads.addToList(fieldset, path);
                    break;
                case 'imagelist':
                    uploads.addToList(fieldset, path);
                    break;
            }

            return false;
        });
    }

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
        var fieldset = $(event.target).closest('div[data-bolt-fieldset]');
        $.each(data.result, function (idx, file) {
            if (file.error) {
                bootbox.alert(bolt.data('field.uploads.template.error', {'%ERROR%': file.error}));
            } else {
                switch ($(fieldset).data('bolt-fieldset')) {
                    case 'file':
                    case 'image':
                        $(fieldset).find('input.path').val(file.name).trigger('change');
                        bolt.stack.addToStack(file.name);
                        break;
                    case 'filelist':
                    case 'imagelist':
                        uploads.addToList(fieldset, file.name);
                        break;
                    default:
                    bolt.stack.addToStack(file.name);
                }
            }
        });
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

        $('.list-item', listField).each(function () {
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

    // Apply mixin container
    bolt.uploads = uploads;

})(Bolt || {}, jQuery);
