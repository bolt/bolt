module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        config: grunt.file.readYAML('../config/config.yml'),

        watch: {
            options: {
                spawn: false,
                livereload: true
            },
            scripts: {
                files: ['sass/*.scss', 'sass/nav/*.scss'],
                tasks: ['sass'],
            },
            js: {
                files: ['js/*.js', 'js/src/*.js'],
                tasks: ['concat:js'],
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
                    banner: "/**\n * These are Bolt's COMPILED CSS files! \n * Do not edit these files, because all changes will be lost. \n * You can edit ../scss/app.scss & ../scss/app-old-ie.scss, and run 'grunt' to generate this file. \n */"
                },
                files: {
                    'css/bolt-old-ie.css': 'sass/app-old-ie.scss',
                    'css/bolt.css': 'sass/app.scss'
                }
            }

        },


        copy: {
            main: {
                files: [
                    // includes files within path
                    {expand: true, flatten: true, src: ['node_modules/font-awesome/fonts/*'], dest: 'fonts/', filter: 'isFile'}
                ]
            }
        },

        // concat: {
        //     bootstrap: {
        //         src: [
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/alert.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/button.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/dropdown.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tab.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/transition.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/modal.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tooltip.js',
        //             'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/popover.js',
        //         ],
        //         dest: 'js/bootstrap-concat.js',
        //     },
        //     bolt: {
        //         // TODO: configure this.
        //         //src: ['src/main.js', 'src/extras.js'],
        //         //dest: 'dist/with_extras.js',
        //     },
        // },

        concat: {
            js: {
                src: [
                    'js/src/console.js',
                    'js/src/fnc-helpers.js',
                    'js/src/init-keyboard-shortcuts.js',
                    'js/src/init-ckeditor.js',
                    'js/src/activity.js',
                    'js/src/bind-fileupload.js',
                    'js/src/make-uri-slug.js',
                    'js/src/video-embed.js',
                    'js/src/geolocation.js',
                    'js/src/upload-files.js',
                    'js/src/upload-images.js',
                    'js/src/obj-sidebar.js',
                    'js/src/obj-files.js',
                    'js/src/obj-stack.js',
                    'js/src/obj-folders.js',
                    'js/src/init.js',
                    'js/src/start.js',
                ],
                dest: 'js/bolt.js',
            },
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
        }

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass', 'concat:js', 'watch']);

};
