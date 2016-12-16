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
     * @param {BindData} data - Editcontent configuration data
     */
    liveEditor.init = function (data) {
        liveEditor.slug = data.singularSlug;

        if (Modernizr.contenteditable) {
            $('#sidebar-live-editor-button, #live-editor-button').bind('click', liveEditor.start);

            $('.close-live-editor').bind('click', liveEditor.stop);
            $('.save-live-editor').bind('click', liveEditor.save);
        } else {
            // If we don't have the features we need
            // Don't let this get used
            $('.live-editor, #sidebar-live-editor-button, #live-editor-button').remove();

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
        var valid = bolt.validation.run($('#editcontent')[0]);

        if (!valid) {
            return;
        }

        // Add Events
        var preventClick = function (e) {
            e.preventDefault();
        };

        var iframeReady = function () {
            var iframe = $('#live-editor-iframe')[0],
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
                var field = $('#editcontent *[name=' + liveEditor.escapejQuery($(this).data('bolt-field')) + ']'),
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

        $('#live-editor-iframe').on('load', iframeReady);

        bolt.liveEditor.active = true;
        $('body').addClass('live-editor-active');
        $('#navpage-primary .navbar-header a').on('click', preventClick);

        var newAction = bolt.conf('paths.root') + 'preview/' + liveEditor.slug;
        $('#editcontent *[name=_live-editor-preview]').val('yes');
        $('#editcontent').attr('action', newAction).attr('target', 'live-editor-iframe').submit();
        $('#editcontent').attr('action', '').attr('target', '_self');
        $('#editcontent *[name=_live-editor-preview]').val('');

        removeEvents = function () {
            $('#live-editor-iframe').off('load', iframeReady);
            $('#navpage-primary .navbar-header a').off('click', preventClick);
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

        $('#savecontinuebutton').click();
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
            var fieldName = $(this).data('bolt-field'),
                field = $('#editcontent [name=' + liveEditor.escapejQuery(fieldName) + ']'),
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
     * Clean contenteditable values for text fields
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
        return selector.replace(/(:|\.|\[|\]|,)/g, "\\$1");
    };

    /**
     * Whether the editor is running or not
     *
     * @public
     * @type {Boolean}
     */
    liveEditor.active = false;

    /**
     * Contenttype slug for the editor
     *
     * @private
     * @type {string}
     */
    liveEditor.slug = null;

    bolt.liveEditor = liveEditor;
})(Bolt || {}, jQuery, window, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
