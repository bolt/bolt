module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        config: grunt.file.readYAML('../config/config.yml'),

        watch: {
            options: {
                spawn: false,
                livereload: true
            },
            scripts: {
                files: [
                    'sass/*.scss',
                    'sass/nav/*.scss',
                    'sass/modules/*.scss'
                ],
                tasks: [
                    'sass'
                ]
            },
            js: {
                files: [
                    'js/*.js',
                    'js/src/*.js'
                ],
                tasks: [
                    'concat:js'
                ]
            }
        },

        sass: {
            dist: {
                options: {
                    style: 'compressed',
                    loadPath: [
                        'node_modules/bootstrap-sass/assets/stylesheets/',
                        'node_modules/font-awesome/scss/'
                    ],
                    lineNumbers: false,
                    unixNewlines: true,
                    banner: "/**\n" +
                            " * These are Bolt's COMPILED CSS files!\n" +
                            " * Do not edit these files, because all changes will be lost.\n" +
                            " * You can edit ../scss/app.scss & ../scss/app-old-ie.scss, and run 'grunt' to generate this file.\n" +
                            " */\n"
                },
                files: {
                    'css/bolt-old-ie.css': 'sass/app-old-ie.scss',
                    'css/bolt.css': 'sass/app.scss'
                }
            }

        },

        copy: {
            main: {
                // Includes files within path
                files: [{
                    expand: true,
                    flatten: true,
                    src: [
                        'node_modules/font-awesome/fonts/*'
                    ],
                    filter: 'isFile',
                    dest: 'fonts/'
                }]
            }
        },

        concat: {
            js: {
                options: {
                    separator: '\n/**********************************************************************************************************************/\n\n',
                    banner: "/**\n" +
                            " * These are Bolt's COMPILED JS files!\n" +
                            " * Do not edit these files, because all changes will be lost.\n" +
                            " * You can edit files in <js/src/*.js> and run 'grunt' to generate this file.\n" +
                            " */\n\n",
                    sourceMap: true,
                    sourceMapStyle: 'link'
                },
                nonull: true,
                src: [
                    'js/src/jslint-conf.js',
                    'js/src/console.js',
                    'js/src/fnc-helpers.js',
                    'js/src/activity.js',
                    'js/src/bind-fileupload.js',
                    'js/src/make-uri-slug.js',
                    'js/src/video-embed.js',
                    'js/src/geolocation.js',
                    'js/src/upload-files.js',
                    'js/src/upload-images.js',
                    'js/src/obj-sidebar.js',
                    'js/src/obj-navpopups.js',
                    'js/src/obj-moments.js',
                    'js/src/obj-files.js',
                    'js/src/obj-stack.js',
                    'js/src/obj-folders.js',
                    'js/src/obj-datetime.js',
                    'js/src/init.js',
                    'js/src/start.js'
                ],
                dest: 'js/bolt.js'
            }
        },

        uglify: {
            bootstrap: {
                files: {
                    'js/bootstrap.js': [
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/alert.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/button.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/dropdown.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tab.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/transition.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/modal.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tooltip.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/popover.js'
                    ]
                }
            }
        },

        jshint: {
            src: ['js/src/*.js']
        }

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass', 'jshint', 'concat:js', 'watch']);

};
