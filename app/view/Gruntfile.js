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
                    'lib/src/*.js'
                ],
                tasks: [
                    'uglify:bolt'
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
            jquery_catchpaste: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-catchpaste-1.0.0p/jquery-catchpaste.min.js': [
                        'lib/jquery-catchpaste-1.0.0p/jquery-catchpaste.js'
                    ]
                }
            },
            jquery_tagcloud: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-tagcloud/jquery-tagcloud.min.js': [
                        'lib/jquery-tagcloud/jquery-tagcloud.js'
                    ]
                }
            },
            jquery_hotkeys: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-hotkeys/jquery-hotkeys.min.js': [
                        'lib/jquery-hotkeys/jquery-hotkeys.js'
                    ]
                }
            },
            jquery_watchchanges: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-watchchanges/jquery-watchchanges.min.js': [
                        'lib/jquery-watchchanges/jquery-watchchanges.js'
                    ]
                }
            },
            jquery_cookie: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-cookie-1.4.0/jquery-cookie.min.js': [
                        'lib/jquery-cookie-1.4.0/jquery-cookie.js'
                    ]
                }
            },
            jquery_formatdatetime: {
                options: {
                    preserveComments: 'some'
                },
                files: {
                    'lib/jquery-formatdatetime-1.1.4/jquery-formatdatetime.min.js': [
                        'lib/jquery-formatdatetime-1.1.4/jquery-formatdatetime.js'
                    ]
                }
            },
            bootstrap_file_input: {
                files: {
                    'lib/bootstrap-file-input/bootstrap-file-input.min.js': [
                        'lib/bootstrap-file-input/bootstrap-file-input.js'
                    ]
                }
            },
            bootstrap: {
                files: {
                    'lib/bootstrap-sass.generated/bootstrap.min.js': [
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
                        'lib/src/console.js',
                        'lib/src/fnc-helpers.js',
                        'lib/src/activity.js',
                        'lib/src/bind-fileupload.js',
                        'lib/src/make-uri-slug.js',
                        'lib/src/video-embed.js',
                        'lib/src/geolocation.js',
                        'lib/src/upload-files.js',
                        'lib/src/obj-sidebar.js',
                        'lib/src/obj-navpopups.js',
                        'lib/src/obj-moments.js',
                        'lib/src/obj-files.js',
                        'lib/src/obj-stack.js',
                        'lib/src/obj-folders.js',
                        'lib/src/obj-datetime.js',
                        'lib/src/extend.js',
                        'lib/src/init.js',
                        'lib/src/start.js'
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
                    site: true,                 // src/extend.js/extend.twig
                    baseurl: true,              // src/extend.js/extend.twig
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
            src: ['lib/src/*.js']
        }

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass', 'jshint', 'uglify', 'cssmin', 'watch']);

};
