module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        config: grunt.file.readYAML('../config/config.yml'),

        filesBoltJs: [
            'lib/bolt/console.js',
            'lib/bolt/fnc-helpers.js',
            'lib/bolt/activity.js',
            'lib/bolt/bind-fileupload.js',
            'lib/bolt/make-uri-slug.js',
            'lib/bolt/video-embed.js',
            'lib/bolt/geolocation.js',
            'lib/bolt/upload-files.js',
            'lib/bolt/obj-sidebar.js',
            'lib/bolt/obj-navpopups.js',
            'lib/bolt/obj-moments.js',
            'lib/bolt/obj-files.js',
            'lib/bolt/obj-stack.js',
            'lib/bolt/obj-folders.js',
            'lib/bolt/obj-datetime.js',
            'lib/bolt/obj-validation.js',
            'lib/bolt/extend.js',
            'lib/bolt/init.js',
            'lib/bolt/start.js'
        ],

        /*
         * MODERNIZR: Modernizr builder
         */
        modernizr: {
            prepare: {
                devFile: "remote",
                outputFile: "lib/tmp/modernizr-custom.js",
                extra: {
                    touch: true,
                    shiv: true,
                    cssclasses: true,
                    load: false
                },
                extensibility: {
                    teststyles: true,
                    prefixes: true
                },
                tests: [
                    'cookies'
                ],
                uglify: false,
                matchCommunityTests: true,
                parseFiles: false
            }
        },

        /*
         * REMOVE: Remove directory and files
         */
        remove: {
            prepareCkeditor: {
                dirList: [
                    'lib/ckeditor/adapters',
                    'lib/ckeditor/samples'
                ]
            },
            cleanupTmp: {
                dirList: [
                    'lib/tmp'
                ]
            }
        },

        /*
         * BOM: Byte Order Mark (BOM) removal
         */
        bom: {
            prepareCkeditor: {
                src: [
                    'lib/ckeditor/**/*.js'
                ]
            }
        }
    });

    require('load-grunt-config')(grunt);

};
