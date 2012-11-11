/**
 * Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.language = 'en';
	config.uiColor = '#E8E8E8';
    config.toolbar = [
        { name: 'styles', items: [ 'Format' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
        { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] },
        { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
//        { name: 'insert', items: [ 'Table', 'HorizontalRule', 'SpecialChar' ] },
//        { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
        { name: 'tools', items: [ 'Source', 'Maximize', 'RemoveFormat' ] },
    ];

    config.removePlugins = 'elementspath';
    config.resize_dir = 'vertical';
};

