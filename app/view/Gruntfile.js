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
                    outputStyle: 'compressed',
                    includePaths: [
                        'node_modules/bootstrap-sass/assets/stylesheets/',
                        'node_modules/font-awesome/scss/'
                    ],
                    lineNumbers: false,
                    unixNewlines: true,
                    banner: "/**\n" +
                            " * These are Bolt's COMPILED CSS files!\n" +
                            " * Do not edit these files, because all changes will be lost.\n" +
                            " * You can edit ../scss/app.scss & ../scss/app-old-ie.scss, and run 'grunt' to generate this file.\n" +
                            " */\n",
                    precision: 5
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
        },

        cssmin: {
            lib: {
                options: {
                    compatibility: 'ie8',
                    relativeTo: './css/',
                    target: './css/'
                },
                files: {
                    'css/lib.css': [
                        'lib/jquery-ui-1.10.3/jquery-ui.custom.min.css',
                        'lib/magnific-popup-0.9.9/magnific-popup.css',
                        'lib/select2-3.5.1/select2.css',
                        'lib/jquery.fileupload-5.26/jquery.fileupload-ui.css'
                    ]
                }
            }
        },

        uglify: {
            bootstrap: {
                files: {
                    'js/bootstrap.min.js': [
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/button.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/transition.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
                        'node_modules/bootstrap-sass/assets/javascripts/bootstrap/popover.js'
                    ]
                }
            },
            bolt: {
                options: {
                    banner: "/**\n" +
                            " * These are Bolt's COMPILED JS files!\n" +
                            " * You can edit files in <js/src/*.js> and run 'grunt' to generate this file.\n" +
                            " */",
                    sourceMap: true
                },
                files: {
                    'js/bolt.min.js': [
                        'js/src/console.js',
                        'js/src/fnc-helpers.js',
                        'js/src/activity.js',
                        'js/src/bind-fileupload.js',
                        'js/src/make-uri-slug.js',
                        'js/src/video-embed.js',
                        'js/src/geolocation.js',
                        'js/src/upload-files.js',
                        'js/src/obj-sidebar.js',
                        'js/src/obj-navpopups.js',
                        'js/src/obj-moments.js',
                        'js/src/obj-files.js',
                        'js/src/obj-stack.js',
                        'js/src/obj-folders.js',
                        'js/src/obj-datetime.js',
                        'js/src/init.js',
                        'js/src/start.js'
                    ]
                }
            }
        },

        jshint: {
            options: {
                browser: true,          // Defines globals exposed by modern browsers
                curly: true,            // Always put curly braces around blocks
                devel: true,            // Defines globals that are usually used for logging/debugging
                immed: true,            // Prohibits the use of immediate function invocations without parentheses
                indent: 4,              // Tab width
                latedef: true,          // Prohibits the use of a variable before it was defined
                maxlen: 120,            // Maximum length of a line
                noarg: true,            // Prohibits the use of arguments.caller and arguments.callee
                nonbsp: true,           // Warns about "non-breaking whitespace" characters
                singleGroups: true,     // Prohibits the use of the grouping operator for single-expression statements
                undef: true,            // Prohibits the use of undeclared variables
                globals: {
                    // Bolt
                    bolt: true,                 // src/console.js
                    FilelistHolder: true,       // src/upload-files.js
                    Files: true,                // src/obj-files.js
                    Folders: true,              // src/obj-folders.js
                    init: true,                 // src/init.js
                    Moments: true,              // src/obj-moments.js
                    Navpopups: true,            // src/obj-navpopups.js
                    Sidebar: true,              // src/obj-sidebar.js
                    Stack: true,                // src/obj-stack.js
                    // Bolt global functions
                    bindFileUpload: true,       // src/bindfileuploads.js
                    bindGeolocation: true,      // src/geolocation.js
                    bindVideoEmbed: true,       // src/video-embed.js
                    getSelectedItems: true,     // src/fnc-helpers.js
                    makeUri: true,              // src/make-uri-slug.js
                    makeUriAjax: true,          // src/make-uri-slug.js
                    stopMakeUri: true,          // src/make-uri-slug.js
                    updateLatestActivity: true, // src/activity.js
                    validateContent: true,      // src/fnc-helpers.js
                    // Vendor
                    $: true,                    // jQuery
                    _: true,                    // underscore.js
                    Backbone: true,             // backbone.min.js
                    bootbox: true,              // bootbox.min.js
                    CKEDITOR: true,             // ckeditor.js
                    CodeMirror: true,           // ckeditor.js
                    google: true,               // Google
                    jQuery: true,               // jQuery
                    moment: true,               // moment.min.js
                    UAParser: true              // ua-parser.min.js
                }
            },
            src: ['js/src/*.js']
        }

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass', 'jshint', 'uglify', 'cssmin', 'watch']);

};
