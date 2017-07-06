/**
 * Set up live editor stuff
 *
 * @mixin
 * @namespace Bolt.liveeditor
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} window - Global window object.
 * @param {Object|undefined} ckeditor - CKEDITOR global or undefined.
 */
(function (bolt, $, window, ckeditor) {
    'use strict';

    /**
     * Bolt.liveEditor mixin container.
     *
     * @private
     * @type {Object}
     */
    var liveEditor = {};

    var editableTypes = [
        'text',
        'html',
        'textarea'
    ];

    /**
     * Removes events bound to live editor.
     *
     * @private
     * @function removeEvents
     */
    var removeEvents = null;

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.liveEditor
     *
     * @param {BindData} data - Content editor configuration data
     */
    liveEditor.init = function (data) {
        liveEditor.previewUrl = data.previewUrl;

        if (Modernizr.contenteditable) {
            $('#sidebar_live_edit, #content_edit_live_edit').bind('click', liveEditor.start);

            $('.close-live-editor').bind('click', liveEditor.stop);
            $('.save-live-editor').bind('click', liveEditor.save);
        } else {
            // If we don't have the features we need
            // Don't let this get used
            $('.live-editor, #sidebar_live_editor, #content_edit_live_edit').remove();

        }
    };

    /**
     * Starts the live editor.
     *
     * @private
     *
     * @static
     * @function start
     * @memberof Bolt.liveEditor
     */
    liveEditor.start = function () {
        // Validate form first
        var editForm = $('form[name="content_edit"]'),
            valid    = bolt.validation.run(editForm[0]);

        if (!valid) {
            return;
        }

        var formEditPreview  = editForm.find('*[name=_live-editor-preview]'),
            navBarHeader     = $('#navpage-primary').find('.navbar-header a'),
            liveEditorIFrame = $('#live-editor-iframe');

        // Add Events
        var preventClick = function (e) {
            e.preventDefault();
        };

        var iframeReady = function () {
            var iframe = liveEditorIFrame[0],
                win = iframe.contentWindow || iframe,
                doc = win.document,
                jq = $(doc);

            jq.on('click', 'a', preventClick);

            var cke = bolt.ckeditor.initcke(win.CKEDITOR),
                editorConfig = cke.editorConfig;

            cke.editorConfig = function (config) {
                editorConfig.bind(this)(config);

                // Remove the source code viewer
                for (var i in config.toolbar) {
                    if (config.toolbar[i].name === 'tools') {
                        var sourceIdx = config.toolbar[i].items.indexOf('Source');

                        if (sourceIdx > -1) {
                            delete config.toolbar[i].items[sourceIdx];
                        }
                    }
                }
            };

            cke.disableAutoInline = false;
            jq.find('[data-bolt-field]').each(function () {
                // Find form field
                var field     = editForm.find('*[name=' + liveEditor.escapejQuery($(this).data('bolt-field')) + ']'),
                    fieldType = field.closest('[data-bolt-fieldset]').data('bolt-fieldset');

                $(this).addClass('bolt-editable');

                if (!$(this).data('no-edit') && editableTypes.indexOf(fieldType) !== -1) {
                    $(this).attr('contenteditable', true);

                    if (fieldType === 'html') {
                        var editor = cke.inline(this, {
                            allowedContent: ''
                        });
                        var saveData = bolt.utils.debounce(function () {
                            editor.element.data('src', editor.getData());
                        }, 500);
                        editor.on('instanceReady', saveData);
                        editor.on('change', saveData);
                    } else {
                        $(this).on('paste', function (e) {
                            var content;

                            e.preventDefault();
                            if (e.originalEvent.clipboardData) {
                                content = e.originalEvent.clipboardData.getData('text/plain');
                                doc.execCommand('insertText', false, content);
                            } else if (win.clipboardData) {
                                content = win.clipboardData.getData('Text');
                                if (win.getSelection) {
                                    win.getSelection().getRangeAt(0).insertNode(doc.createTextNode(content));
                                }
                            }
                        });

                        if (fieldType === 'textarea') {
                            $(this).on('keypress', function (e) {
                                if (e.which === 13) {
                                    e.preventDefault();
                                    doc.execCommand('insertHTML', false, '<br><br>');
                                }
                            });
                        } else {
                            $(this).on('keypress', function (e) {
                                return e.which !== 13;
                            }).on('focus blur', function () {
                                $(this).html($(this).text());
                            });
                        }
                    }
                }
            });
        };

        liveEditorIFrame.on('load', iframeReady);
        bolt.liveEditor.active = true;
        $('body').addClass('live-editor-active');

        navBarHeader.on('click', preventClick);
        formEditPreview.val('yes');
        editForm.attr('action', liveEditor.previewUrl).attr('target', 'live-editor-iframe').submit();
        editForm.attr('action', '').attr('target', '_self');
        formEditPreview.val('');

        removeEvents = function () {
            liveEditorIFrame.off('load', iframeReady);
            navBarHeader.off('click', preventClick);
        };
    };

    /**
     * Saves within the live editor.
     *
     * @private
     *
     * @static
     * @function save
     * @memberof Bolt.liveEditor
     */
    liveEditor.save = function () {
        liveEditor.extractText();

        $('#content_edit_save').trigger('click');
    };

    /**
     * Stops the live editor.
     *
     * @private
     *
     * @static
     * @function stop
     * @memberof Bolt.liveEditor
     */
    liveEditor.stop = function () {
        var iframe = $('#live-editor-iframe')[0];

        liveEditor.extractText();

        $(iframe).attr('src', '');

        bolt.liveEditor.active = false;
        $('body').removeClass('live-editor-active');

        removeEvents();
    };

    /**
     * Extract content from text fields
     *
     * @public
     *
     * @function extractText
     * @memberof Bolt.liveEditor
     */
    liveEditor.extractText = function () {
        var iframe = $('#live-editor-iframe')[0],
            win = iframe.contentWindow || iframe,
            doc = win.document,
            jq = $(doc);

        jq.find('[data-bolt-field]').each(function () {
            // Find form field
            var editForm = $('form[name="content_edit"]'),
                fieldName = $(this).data('bolt-field'),
                field = editForm.find('[name=' + liveEditor.escapejQuery(fieldName) + ']'),
                fieldType = field.closest('[data-bolt-fieldset]').data('bolt-fieldset');

            if (fieldType === 'html') {
                var fieldId = field.attr('id');

                if (ckeditor.instances.hasOwnProperty(fieldId)) {
                    ckeditor.instances[fieldId].setData($(this).data('src'));
                }
            } else {
                field.val(liveEditor.cleanText($(this), fieldType));
            }
        });
    };

    /**
     * Clean content editable values for text fields.
     *
     * @public
     *
     * @function cleanText
     * @memberof Bolt.liveEditor
     *
     * @param {Object} element - jQuery element to clean
     * @param {String} fieldType - type of field to clean (text, textarea)
     * @return {String} Value for editcontent input fields
     */
    liveEditor.cleanText = function (element, fieldType) {
        // Preserve newlines and spacing for textarea fields
        if (fieldType === 'textarea') {
            element.html(element.html().replace(/&nbsp;/g, ' ').replace(/\s?<br.*?>\s?/g, '\n'));
        }

        return element.text();
    };

    /**
     * Escape jQuery selectors
     *
     * @public
     *
     * @static
     * @function escapejQuery
     * @memberof Bolt.liveEditor
     *
     * @param {String} selector - Selector to escape
     */
    liveEditor.escapejQuery = function (selector) {
        return selector.replace(/[:\.\[\],]/g, "\\$1");
    };

    /**
     * Whether the editor is running or not
     *
     * @public
     * @type {Boolean}
     */
    liveEditor.active = false;

    /**
     * The preview url
     *
     * @private
     * @type {string}
     */
    liveEditor.previewUrl = null;

    bolt.liveEditor = liveEditor;
})(Bolt || {}, jQuery, window, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
