/**
 * Initialise CKEditor instances if available.
 *
 * @mixin
 * @namespace Bolt.ckeditor
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object|undefined} cke - CKEDITOR global or undefined.
 */
(function (bolt, $, cke) {
    /**
     * Bolt.ckeditor mixin container.
     *
     * @private
     * @type {Object}
     */
    var ckeditor = {};

    /**
     * Initialise all CKEditor instances, if available.
     *
     * @static
     * @function init
     * @memberof Bolt.ckeditor
     */
    ckeditor.init = function () {
        if (cke) {
            init();
        }
    };

    /**
     * Update the underlying textareas with the content in the CKEditor fields.
     *
     * @private
     *
     * @static
     * @function update
     * @memberof Bolt.ckeditor
     */
    ckeditor.update = function () {
        // First, make sure
        if (cke) {
            for (var instance in cke.instances) {
                cke.instances[instance].updateElement();
            }
        }
    };

    /**
     * Initialise CKEditor instances.
     *
     * @private
     *
     * @static
     * @function init
     * @memberof Bolt.ckeditor
     */
    function init() {
        cke.editorConfig = function (config) {
            var key,
                param = $(this.element.$).data('param') || {},
                set = bolt.conf('ckeditor');

            var basicStyles = ['Bold', 'Italic'];
            var linkItems = ['Link', 'Unlink'];
            var toolItems = [ 'RemoveFormat', 'Maximize', '-', 'Source'];
            var paragraphItems = ['NumberedList', 'BulletedList', 'Indent', 'Outdent'];

            if (set.underline) {
                basicStyles = basicStyles.concat('Underline');
            }
            if (set.strike) {
                basicStyles = basicStyles.concat('Strike');
            }
            if (set.anchor) {
                linkItems = linkItems.concat('-', 'Anchor');
            }
            if (set.specialchar) {
                toolItems = ['SpecialChar', '-'].concat(toolItems);
            }
            if (set.blockquote) {
                paragraphItems = paragraphItems.concat('-', 'Blockquote');
            }

            config.language = bolt.conf('locale.short');
            config.uiColor = '#DDDDDD';
            config.resize_enabled = true;
            config.entities = false;
            config.fillEmptyBlocks = false;
            config.extraPlugins = 'codemirror';
            config.toolbar = [
                { name: 'styles', items: ['Format'] },
                { name: 'basicstyles', items: basicStyles }, // ['Bold', 'Italic', 'Underline', 'Strike']
                { name: 'paragraph', items: paragraphItems },
                { name: 'links', items: linkItems }
            ];


            if (set.subsuper) {
                config.toolbar = config.toolbar.concat({
                    name: 'subsuper', items: ['Subscript', 'Superscript']
                });
            }
            if (set.images) {
                config.toolbar = config.toolbar.concat({
                    name: 'image', items: ['Image']
                });
            }
            if (set.embed) {
                config.extraPlugins += ',oembed,widget';
                config.oembed_maxWidth = '853';
                config.oembed_maxHeight = '480';
                config.toolbar = config.toolbar.concat({
                    name: 'embed', items: ['oembed']
                });
            }

            if (set.tables) {
                config.toolbar = config.toolbar.concat({
                    name: 'table', items: ['Table']
                });
            }
            if (set.align) {
                config.toolbar = config.toolbar.concat({
                    name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                });
            }
            if (set.fontcolor) {
                config.toolbar = config.toolbar.concat({
                    name: 'colors', items: ['TextColor', 'BGColor']
                });
            }

            if (set.codesnippet) {
                config.toolbar = config.toolbar.concat({
                    name: 'code', items: ['-', 'CodeSnippet']
                });
            }

            config.toolbar = config.toolbar.concat({
                name: 'tools', items: toolItems
            });

            config.height = 250;
            config.autoGrow_onStartup = true;
            config.autoGrow_minHeight = 150;
            config.autoGrow_maxHeight = 400;
            config.autoGrow_bottomSpace = 24;
            config.removePlugins = 'elementspath';
            config.resize_dir = 'vertical';

            if (set.filebrowser) {
                if (set.filebrowser.browseUrl) {
                    config.filebrowserBrowseUrl = set.filebrowser.browseUrl;
                }
                if (set.filebrowser.imageBrowseUrl) {
                    config.filebrowserImageBrowseUrl = set.filebrowser.imageBrowseUrl;
                }
                if (set.filebrowser.uploadUrl) {
                    config.filebrowserUploadUrl = set.filebrowser.uploadUrl;
                }
                if (set.filebrowser.imageUploadUrl) {
                    config.filebrowserImageUploadUrl = set.filebrowser.imageUploadUrl;
                }
            } else {
                config.filebrowserBrowseUrl = '';
                config.filebrowserImageBrowseUrl = '';
                config.filebrowserUploadUrl = '';
                config.filebrowserImageUploadUrl = '';
            }

            config.codemirror = {
                theme: 'default',
                lineNumbers: true,
                lineWrapping: true,
                matchBrackets: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                enableSearchTools: true,
                enableCodeFolding: true,
                enableCodeFormatting: false,
                autoFormatOnStart: false,
                autoFormatOnUncomment: false,
                autoFormatOnModeChange: false,
                highlightActiveLine: true,
                highlightMatches: true,
                showFormatButton: false,
                showCommentButton: false,
                showUncommentButton: false
            };

            // Parse override settings from config.yml
            for (key in set.ck) {
                if (set.ck.hasOwnProperty(key)) {
                     config[key] = set.ck[key];
                }
            }

            // Parse override settings from field in contenttypes.yml
            for (key in param.ckeditor) {
                if (param.ckeditor.hasOwnProperty(key)) {
                    config[key] = param.ckeditor[key];
                }
            }
            console.log(this.element.$);
        };

        // When 'pasting' from Word (or perhaps other editors too), you'll often
        // get extra `&nbsp;&nbsp;` or `<p>&nbsp;</p>`. Strip these out on paste:
        cke.on('instanceReady', function (ev) {
            ev.editor.on('paste', function (evt) {
                evt.data.dataValue = evt.data.dataValue.replace(/&nbsp;/g, ' ');
                evt.data.dataValue = evt.data.dataValue.replace(/<p> <\/p>/g, '');
            }, null, null, 9);
        });
    }

    // Apply mixin container
    bolt.ckeditor = ckeditor;

})(Bolt || {}, jQuery, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
