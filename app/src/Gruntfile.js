module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    var options = {
        path: {
            tmp: 'tmp',
            doc: 'docs',
            src: {
                js: 'js',
                lib: 'lib',
                sass: 'sass',
                node: 'node_modules',
                bower: 'bower_components'
            },
            dest: {
                js: '../view/js',
                fonts: '../view/fonts',
                img: '../view/img',
                css: '../view/css'
            }
        },

        files: {
            boltJs: [
                // Prerequisites
                '<%= path.src.js %>/console.js',
                '<%= path.src.js %>/class-extends.js',
                // Bolt module
                '<%= path.src.js %>/bolt.js',
                '<%= path.src.js %>/modules/app.js',
                '<%= path.src.js %>/modules/conf.js',
                '<%= path.src.js %>/modules/data.js',
                '<%= path.src.js %>/modules/actions.js',
                '<%= path.src.js %>/modules/ckeditor.js',
                '<%= path.src.js %>/modules/files.js',
                '<%= path.src.js %>/modules/stack.js',
                '<%= path.src.js %>/modules/video.js',
                '<%= path.src.js %>/modules/datetime.js',
                '<%= path.src.js %>/modules/activity.js',
                '<%= path.src.js %>/modules/slug.js',
                // Old stuff
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
                '<%= path.src.js %>/obj-datetime.js',
                '<%= path.src.js %>/obj-validation.js',
                '<%= path.src.js %>/extend.js',
                '<%= path.src.js %>/init.js'
            ]
        },

        banner: {
            boltJs: [
                '/**',
                ' * These are Bolt’s COMPILED JS files!',
                ' * You can edit *.js files in /app/src/js/ and then run "grunt updateBolt" to generate this file.',
                ' */'
            ].join('\n'),
            boltCss: [
                '/**',
                ' * These are Bolt’s COMPILED CSS files!',
                ' * Do not edit these files, because all changes will be lost.',
                ' * You can edit *.scss files in /app/src/scss/ and then run "grunt updateBolt" to generate this file.',
                ' */'
            ].join('\n')
        }
    };

    require('load-grunt-config')(grunt, {data: options});

};
