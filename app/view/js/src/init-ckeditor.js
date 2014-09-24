/**
 * Initialise CKeditor instances.
 */
CKEDITOR.editorConfig = function( config ) {
    config.language = ckeditor_lang || 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = true;
    config.entities = false;
    config.extraPlugins = 'codemirror';
    config.toolbar = [
        { name: 'styles', items: [ 'Format' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
        { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] }
    ];

    if (wysiwyg.anchor) {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink', '-', 'Anchor' ] });
    } else {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink' ] });
    }

    if (wysiwyg.subsuper) {
        config.toolbar = config.toolbar.concat({ name: 'subsuper', items: [ 'Subscript', 'Superscript' ] });
    }
    if (wysiwyg.images) {
        config.toolbar = config.toolbar.concat({ name: 'image', items: [ 'Image' ] });
    }
    if (wysiwyg.embed) {
        config.extraPlugins += ',oembed,widget';
        config.oembed_maxWidth = '853';
        config.oembed_maxHeight = '480';
        config.toolbar = config.toolbar.concat({ name: 'embed', items: [ 'oembed' ] });
    }

    if (wysiwyg.tables) {
        config.toolbar = config.toolbar.concat({ name: 'table', items: [ 'Table' ] });
    }
    if (wysiwyg.align) {
        config.toolbar = config.toolbar.concat({ name: 'align', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] });
    }
    if (wysiwyg.fontcolor) {
        config.toolbar = config.toolbar.concat({ name: 'colors', items: [ 'TextColor', 'BGColor' ] });
    }

    config.toolbar = config.toolbar.concat({ name: 'tools', items: [ 'SpecialChar', '-', 'RemoveFormat', 'Maximize', '-', 'Source' ] });

    config.height = 250;
    config.autoGrow_onStartup = true;
    config.autoGrow_minHeight = 150;
    config.autoGrow_maxHeight = 400;
    config.autoGrow_bottomSpace = 24;
    config.removePlugins = 'elementspath';
    config.resize_dir = 'vertical';

    if (wysiwyg.filebrowser) {
        if (wysiwyg.filebrowser.browseUrl) {
            config.filebrowserBrowseUrl = wysiwyg.filebrowser.browseUrl;
        }
        if (wysiwyg.filebrowser.imageBrowseUrl) {
            config.filebrowserImageBrowseUrl = wysiwyg.filebrowser.imageBrowseUrl;
        }
        if (wysiwyg.filebrowser.uploadUrl) {
            config.filebrowserUploadUrl = wysiwyg.filebrowser.uploadUrl;
        }
        if (wysiwyg.filebrowser.imageUploadUrl) {
            config.filebrowserImageUploadUrl = wysiwyg.filebrowser.imageUploadUrl;
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

    /* Parse override settings from config.yml */
    for (var key in wysiwyg.ck){
        if (wysiwyg.ck.hasOwnProperty(key)) {
             config[key] = wysiwyg.ck[key];
        }
    }
};

