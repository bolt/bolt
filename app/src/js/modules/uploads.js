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
        uploads.bindUpload(fieldset.id, false);

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
     * @function bindListField
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     * @param {string} type
     */
    uploads.bindListField = function (fieldset, type) {
        var lastClick = null,
            isFile = type === 'filelist',
            message = {
                removeSingle: isFile ? 'field.filelist.message.remove' : 'field.imagelist.message.remove',
                removeMulti: isFile ? 'field.filelist.message.removeMulti' : 'field.imagelist.message.removeMulti'
            };

        $('div.list', fieldset)
            .sortable({
                helper: function (evt, item) {
                    if (!item.hasClass('selected')) {
                        item.toggleClass('selected');
                    }

                    return $('<div></div>');
                },
                start: function (evt, ui) {
                    var elements = fieldset.find('.selected').not('.ui-sortable-placeholder'),
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
                    fieldset.find('.selected').show();
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
                        fieldset.find('.list-item').not($(this)).removeClass('selected');
                        $(this).toggleClass('selected');
                    }

                    lastClick = evt.shiftKey || evt.ctrlKey || evt.metaKey || $(this).hasClass('selected') ?
                        $(this) : null;
                }
            })
            .on('click', '.remove-button', function (evt) {
                evt.preventDefault();

                if (confirm(Bolt.data(message.removeSingle))) {
                    $(this).closest('.list-item').remove();
                    serializeList(fieldset);
                }
            })
            .on('change', 'input', function () {
                serializeList(fieldset);
            });

        $(fieldset).find('.remove-selected-button').on('click', function () {
            if (confirm(Bolt.data(message.removeMulti))) {
                fieldset.find('.selected').closest('.list-item').remove();
                serializeList(fieldset);
            }
        });

        uploads.bindUpload(fieldset.id, true);
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
        uploads.bindListField(fieldset, 'filelist');
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
        uploads.bindListField(fieldset, 'imagelist');
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
     * @param {boolean} isList
     */
    uploads.bindUpload = function (fieldId, isList) {
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
                if (isList) {
                    $.each(data.result, function (idx, file) {
                        uploads.addToList(fieldset, file.name, file.name);
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
     * Adds a file to an upload list.
     *
     * @static
     * @function addToList
     * @memberof Bolt.uploads
     * @param {Object} fieldset
     * @param {string} filename
     * @param {string} title
     */
    uploads.addToList = function (fieldset, filename, title) {
        var listField = $('div.list', fieldset),
            type = fieldset.data('bolt-field'),
            templateItem = type === 'filelist' ? 'field.filelist.template.item' : 'field.imagelist.template.item';

        // Remove empty list message, if there.
        $('>p', listField).remove();

        // Append to list.
        listField.append(
            $(Bolt.data(
                templateItem,
                {
                    '%TITLE_A%':    title,
                    '%FILENAME_E%': $('<div>').text(filename).html(), // Escaped
                    '%FILENAME_A%': filename
                }
            ))
        );

        serializeList(fieldset);
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
            isFile = $(fieldset).data('bolt-field') === 'filelist',
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
