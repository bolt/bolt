/*
 * Bolt module: Ckeditor
 *
 * Initialise CKEditor instances.
 *
 * @type {function}
 * @mixin
 */
var BoltCkeditor = (function (bolt, $, ckeditor) {
    /*
     * Initialise CKEditor instances.
     */
    function init() {
        ckeditor.editorConfig = function (config) {
            var key,
                custom,
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
            custom = $('textarea[name=' + this.name + ']').data('field-options');
            for (key in custom) {
                if (custom.hasOwnProperty(key)) {
                    config[key] = custom[key];
                }
            }
        };

        // When 'pasting' from Word (or perhaps other editors too), you'll often
        // get extra `&nbsp;&nbsp;` or `<p>&nbsp;</p>`. Strip these out on paste:
        ckeditor.on('instanceReady', function (ev) {
            ev.editor.on('paste', function (evt) {
                evt.data.dataValue = evt.data.dataValue.replace(/&nbsp;/g, ' ');
                evt.data.dataValue = evt.data.dataValue.replace(/<p> <\/p>/g, '');
            }, null, null, 9);
        });
    };

    /*
     * BoltCkeditor mixin.
     */
    bolt.ckeditor = {};

    /*
     * Initialise CKEditor instances.
     */
    bolt.ckeditor.init = function () {
        if (typeof ckeditor !== 'undefined') {
            init();
        }
    };

    return bolt;
})(Bolt || {}, jQuery, CKEDITOR);
