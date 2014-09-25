/**
 * Initialise CKeditor instances.
 */
CKEDITOR.editorConfig = function (config) {
    var key, custom;

    config.language = bolt.ckeditorLang || 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = true;
    config.entities = false;
    config.extraPlugins = 'codemirror';
    config.toolbar = [
        { name: 'styles', items: ['Format'] },
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote'] }
    ];

    if (bolt.wysiwyg.anchor) {
        config.toolbar = config.toolbar.concat({
            name: 'links', items: ['Link', 'Unlink', '-', 'Anchor']
        });
    } else {
        config.toolbar = config.toolbar.concat({
            name: 'links', items: ['Link', 'Unlink']
        });
    }

    if (bolt.wysiwyg.subsuper) {
        config.toolbar = config.toolbar.concat({
            name: 'subsuper', items: ['Subscript', 'Superscript']
        });
    }
    if (bolt.wysiwyg.images) {
        config.toolbar = config.toolbar.concat({
            name: 'image', items: ['Image']
        });
    }
    if (bolt.wysiwyg.embed) {
        config.extraPlugins += ',oembed,widget';
        config.oembed_maxWidth = '853';
        config.oembed_maxHeight = '480';
        config.toolbar = config.toolbar.concat({
            name: 'embed', items: ['oembed']
        });
    }

    if (bolt.wysiwyg.tables) {
        config.toolbar = config.toolbar.concat({
            name: 'table', items: ['Table']
        });
    }
    if (bolt.wysiwyg.align) {
        config.toolbar = config.toolbar.concat({
            name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
        });
    }
    if (bolt.wysiwyg.fontcolor) {
        config.toolbar = config.toolbar.concat({
            name: 'colors', items: ['TextColor', 'BGColor']
        });
    }

    config.toolbar = config.toolbar.concat({
        name: 'tools', items: ['SpecialChar', '-', 'RemoveFormat', 'Maximize', '-', 'Source']
    });

    config.height = 250;
    config.autoGrow_onStartup = true;
    config.autoGrow_minHeight = 150;
    config.autoGrow_maxHeight = 400;
    config.autoGrow_bottomSpace = 24;
    config.removePlugins = 'elementspath';
    config.resize_dir = 'vertical';

    if (bolt.wysiwyg.filebrowser) {
        if (bolt.wysiwyg.filebrowser.browseUrl) {
            config.filebrowserBrowseUrl = bolt.wysiwyg.filebrowser.browseUrl;
        }
        if (bolt.wysiwyg.filebrowser.imageBrowseUrl) {
            config.filebrowserImageBrowseUrl = bolt.wysiwyg.filebrowser.imageBrowseUrl;
        }
        if (bolt.wysiwyg.filebrowser.uploadUrl) {
            config.filebrowserUploadUrl = bolt.wysiwyg.filebrowser.uploadUrl;
        }
        if (bolt.wysiwyg.filebrowser.imageUploadUrl) {
            config.filebrowserImageUploadUrl = bolt.wysiwyg.filebrowser.imageUploadUrl;
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
        enableCodeFormatting: true,
        autoFormatOnStart: true,
        autoFormatOnUncomment: true,
        highlightActiveLine: true,
        highlightMatches: true,
        showFormatButton: false,
        showCommentButton: false,
        showUncommentButton: false
    };

    // Parse override settings from config.yml
    for (key in bolt.wysiwyg.ck) {
        if (bolt.wysiwyg.ck.hasOwnProperty(key)) {
             config[key] = bolt.wysiwyg.ck[key];
        }
    }

    // Parse override settings from field in contenttypes.yml
    custom = $('textarea[name=' + this.name + ']').data('ckconfig');
    if ($.isArray(custom)) {
        for (key in custom){
            if (custom.hasOwnProperty(key)) {
                config[key] = custom[key];
            }
        }
    }
};
