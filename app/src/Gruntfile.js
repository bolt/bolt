module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    var options = {
        path: {
            tmp: 'tmp',
            src: {
                js: 'js',
                lib: 'lib',
                sass: 'sass',
                node: 'components/node_modules',
                bower: 'components/bower_components'
            },
            dest: {
                js: '../view/js',
                fonts: '../view/fonts',
                img: '../view/img',
                css: '../view/css',
                maps: '../view/maps'
            }
        },

        files: {
            boltJs: [
                '<%= path.src.js %>/console.js',
                '<%= path.src.js %>/fnc-helpers.js',
                '<%= path.src.js %>/activity.js',
                '<%= path.src.js %>/bind-fileupload.js',
                '<%= path.src.js %>/make-uri-slug.js',
                '<%= path.src.js %>/video-embed.js',
                '<%= path.src.js %>/geolocation.js',
                '<%= path.src.js %>/upload-files.js',
                '<%= path.src.js %>/obj-sidebar.js',
                '<%= path.src.js %>/obj-navpopups.js',
                '<%= path.src.js %>/obj-moments.js',
                '<%= path.src.js %>/obj-files.js',
                '<%= path.src.js %>/obj-stack.js',
                '<%= path.src.js %>/obj-folders.js',
                '<%= path.src.js %>/obj-datetime.js',
                '<%= path.src.js %>/obj-validation.js',
                '<%= path.src.js %>/extend.js',
                '<%= path.src.js %>/init.js',
                '<%= path.src.js %>/start.js'
            ]
        },

        banner: {
            boltJs: [
                '/**',
                ' * These are Bolt’s COMPILED JS files!',
                ' * You can edit *.js files in <../lib/bolt/> and then run “grunt updateBolt” to generate this file.',
                ' */'
            ].join('\n'),
            boltCss: [
                '/**',
                ' * These are Bolt’s COMPILED CSS files!',
                ' * Do not edit these files, because all changes will be lost.',
                ' * You can edit *.scss files in <../scss/> and then run “grunt updateBolt” to generate this file.',
                ' */'
            ].join('\n')
        }
    };

    require('load-grunt-config')(grunt, {data: options});

};
