(function() {
    var extras = {
        anchor: {
            name: 'links',
            items: ['Link', 'Unlink', '-', 'Anchor']
        },
        links: {
            name: 'links',
            items: ['Link', 'Unlink']
        },
        subsuper: {
            name: 'subsuper',
            items: ['Subscript', 'Superscript']
        },
        mediaembed: {
            name: 'embed',
            items: ['MediaEmbed']
        },
        align: {
            name: 'align',
            items: ['JustifyLeft',
                    'JustifyCenter',
                    'JustifyRight',
                    'JustifyBlock']
        },
        colors: {
            name: 'colors',
            items: ['TextColor', 'BGColor']
        },
        tools: {
            name: 'tools',
            items: ['SpecialChar',
                    '-',
                    'RemoveFormat',
                    'Maximize',
                    '-',
                    'Source']
        }
    };
    var toolbar = [{
        name: 'inlinesave',
        items: ['EditableSave']
    }, {
        name: 'styles',
        items: ['Format']
    }, {
        name: 'basicstyles',
        items: ['Bold', 'Italic', 'Underline', 'Strike']
    }, {
        name: 'paragraph',
        items: ['NumberedList',
                'BulletedList',
                'Indent',
                'Outdent',
                '-',
                'Blockquote']
    },
    {
        name: 'table',
        items: ['Table']
    }];

    CKEDITOR.plugins.addExternal('editable', '../../../extensions/Editable/assets/ckeditor/plugins/editable/','plugin.js');
    CKEDITOR.config.extraPlugins = 'editable';
    CKEDITOR.config.autoParagraph = false;

    CKEDITOR.on('instanceCreated', function(event) {
        var editor = event.editor;
        var $element = $(editor.element.$);
        var options = $element.data('options') || [];
        var tbItems = toolbar;

        if (typeof options == "string") {
            options = options.split(',');
        }
        for (var i = 0; i < options.length; i++) {
            var menuItem = extras[options[i]];
            if (typeof menuItem == "object") {
                tbItems = tbItems.concat(menuItem);
            }
        }

        editor.on('configLoaded', function() {
            editor.config.toolbar = tbItems;
        });

    });
    /*
     * @todo Implement snapshot capturing of a block and following changes
     */
})();
