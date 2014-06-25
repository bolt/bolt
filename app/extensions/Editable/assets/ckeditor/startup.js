(function() {
    var MSG_EDITABLE = 'You can edit this field by a click with ';
    var MSG_CONTENTINFO = 'The content will be edited is: ';

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
        },
        image: {
            name: 'image',
            items: ['Image']
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

    CKEDITOR.plugins.addExternal('editable', '../../../extensions/Editable/assets/ckeditor/plugins/editable/', 'plugin.js');
    CKEDITOR.config.extraPlugins = 'editable';
    CKEDITOR.config.autoParagraph = false;

    $(document).ready(function() {
        $('body').append('<div id="ext-editable-popup" style="display:none"/>');
    });

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

        editor.on('instanceReady', function() {
            var target = JSON.parse($element.attr('data-parameters'));
            $element.attr('title', MSG_EDITABLE + $element.attr('title') + '.\n'
                                  + MSG_CONTENTINFO + target.contenttypeslug
                                  + '@' + target.fieldname);
        });

    });
    /*
     * @todo Implement snapshot capturing of a block and following changes
     */
})();
