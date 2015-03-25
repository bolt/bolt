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

    /**
     * Bolt.liveEditor mixin container.
     *
     * @private
     * @type {Object}
     */
    var liveEditor = {};

    var editcontent = bolt.editcontent;

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.liveEditor
     *
     * @param {BindData} data - Editcontent configuration data
     */
    liveEditor.init = function(data) {
        var editor = $('#live-editor-iframe');
        liveEditor.slug = data.singularSlug;

        if (Modernizr.contenteditable) {
            $('#sidebar-live-editor-button, #live-editor-button').bind('click', liveEditor.start);

            $('.close-live-editor').bind('click', liveEditor.stop);
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
     *
     * @param {Event} event - Triggering event
     */
    liveEditor.start = function(e) {
        // Add Events
        var iframeReady = function() {
            var iframe = $('#live-editor-iframe')[0];
            var win = iframe.contentWindow || iframe;
            var doc = win.document;
            var jq = $(doc);
            var cke = bolt.ckeditor.initcke(win.CKEDITOR);
            var editorConfig = cke.editorConfig;
            cke.editorConfig = function(config) {
                editorConfig.bind(this)(config);

                // Remove the source code viewer
                var ind;
                _.each(config.toolbar, function(ob, i) {
                    if (ob.name === 'tools') {
                        ind = i;
                    }
                });
                config.toolbar[ind] = _.without(config.toolbar[ind], 'Source');
            };

            cke.disableAutoInline = false;
            jq.find('[data-bolt-field]').each(function() {
                // Find form field
                var field = $('#editcontent *[name=' + liveEditor.escapejQuery($(this).data('bolt-field')));
                var fieldType = field.closest('[data-fieldtype]').data('fieldtype');

                $(this).addClass('bolt-editable');

                if ((!$(this).data('no-edit')) && ((fieldType === 'text') || (fieldType === 'html'))) {

                    $(this).attr('contenteditable', true);

                    if (fieldType == 'html') {
                        var editor = cke.inline(this, {
                            allowedContent: ''
                        });
                    } else {
                        $(this).on('paste', function(e) {
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
                        }).on('keypress', function (e) {
                            return e.which != 13;
                        }).on('focus blur', function (e) {
                            $(this).html($(this).text());
                        });
                    }
                }
            });
        };

        $('#live-editor-iframe').on('load', iframeReady);

        liveEditor.removeEvents = function() {
           $('#live-editor-iframe').off('load', iframeReady);
        };

        bolt.liveEditor.active = true;
        clearTimeout(bolt.sidebar.lengthTimer);
        $('#navpage-secondary').css('height', '');
        $('body').addClass('live-editor-active');

        var newAction = bolt.conf('paths.root') + 'preview/' + liveEditor.slug;
        $('#editcontent *[name=_live-editor-preview]').val('yes');
        $('#editcontent').attr('action', newAction).attr('target', 'live-editor-iframe').submit();
        $('#editcontent').attr('action', '').attr('target', '_self');
        $('#editcontent *[name=_live-editor-preview]').val('');
    };

    /**
     * Stops the live editor.
     *
     * @private
     *
     * @static
     * @function stop
     * @memberof Bolt.liveEditor
     *
     * @param {Event} event - Triggering event
     */
    liveEditor.stop = function (e) {
        var iframe = $('#live-editor-iframe')[0];
        var win = iframe.contentWindow || iframe;
        var doc = win.document;
        var jq = $(doc);

        jq.find('[data-bolt-field]').each(function() {
            // Find form field
            var fieldName = $(this).data('bolt-field');
            var field = $('#editcontent [name=' + liveEditor.escapejQuery(fieldName));
            var fieldType = field.closest('[data-fieldtype]').data('fieldtype');

            if (fieldType === 'text') {
                field.val($(this).text());
            } else {
                if (_.has(ckeditor.instances, fieldName)) {
                    ckeditor.instances[fieldName].setData($(this).html());
                }
                field.val($(this).html());
            }
        });

        $(iframe).attr('src', '');

        bolt.liveEditor.active = false;
        $('body').removeClass('live-editor-active');
        bolt.sidebar.fixlength();

        liveEditor.removeEvents();
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
    liveEditor.escapejQuery = function(selector) {
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

    /**
     * Removes events bound to live editor
     *
     * @private
     * @function removeEvents
     */
    liveEditor.removeEvents = null;

    bolt.liveEditor = liveEditor;
})(Bolt || {}, jQuery, window, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
