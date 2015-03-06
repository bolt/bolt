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
         * WATCH: Run predefined tasks whenever watched file patterns are added, changed or deleted
         */
        watch: {
            options: {
                spawn: false,
                livereload: true
            },
            sass: {
                files: [
                    'sass/**/*.scss'
                ],
                tasks: [
                    'sass:boltCss'
                ]
            },
            boltJs: {
                files: [
                    "<%= filesBoltJs %>"
                ],
                tasks: [
                    'jshint:boltJs',
                    'uglify:boltJs'
                ]
            },
            gruntfile: {
                files: [
                    'Gruntfile.js'
                ],
                options: {
                    reload: true
                }
            }
        },

        /*
         * SASS: Compile Sass to CSS
         */
        sass: {
            boltCss: {
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

        /*
         * EOL: Convert line endings
         */
        eol: {
            prepareCkeditor: {
                options: {
                    eol: 'lf',
                    replace: true
                },
                files: {
                    src: [
                        'lib/ckeditor/**/*.js',
                        'lib/ckeditor/**/*.css',
                        'lib/ckeditor/**/*.md',
                        'lib/ckeditor/**/*.txt'
                    ]
                }
            }
        },

        /*
         * ENDLINE: Adds a newline at end of a file
         */
        endline: {
            prepareCkeditor: {
                options: {
                    replaced: true
                },
                src: [
                    'lib/ckeditor/**/*.js'
                ],
                dest: false
            }
        },

        /*
         * COPY: Copy files and folders
         */
        copy: {
            installFonts: {
                files: [{
                    expand: true,
                    flatten: true,
                    src: [
                        'node_modules/font-awesome/fonts/*'
                    ],
                    filter: 'isFile',
                    dest: 'fonts/'
                }]
            },
            installCkeditor1: {
                files: [
                    {
                        // Copy all CKEditor files
                        expand: true,
                        cwd: 'lib/ckeditor',
                        src: [
                            'plugins/**',
                            'skins/**',
                            'styles.js'
                        ],
                        dest: 'js/ckeditor'
                    }, {
                        // Copy our empty config file
                        src: 'lib/bolt/ckeditor-config.js',
                        dest: 'js/ckeditor/config.js'
                    }, {
                        // Copy style to css folder
                        src: 'lib/ckeditor/contents.css',
                        dest: 'css/ckeditor-contents.css'
                    }, {
                        // Copy CKEditor locale
                        expand: true,
                        flatten: true,
                        src: 'lib/ckeditor/lang/*.js',
                        dest: 'js/locale/ckeditor'
                    }
                ]
            },
            installCkeditor2: {
                // process doesn't work on file level, so we need a new target
                src: 'lib/ckeditor/ckeditor.js',
                dest: 'js/ckeditor/ckeditor.js',
                options: {
                    process: function (cont) {
                        return cont.replace(/(CKEDITOR\.getUrl\()"lang\/"(\+a\+"\.js"\))/, '$1"../locale/ckeditor/"$2');
                    }
                }
            },
            installJqueryGomap: {
                src: 'lib/jquery-gomap/jquery-gomap.min.js',
                dest: 'js/jquery-gomap.min.js'
            }
        },

        /*
         * CONCAT: Concatenate files
         */
        concat: {
            installLibJs: {
                options: {
                    separator: '\n\n',
                    sourceMap: true,
                    sourceMapName: 'js/maps/lib.min.js.map'
                },
                nonull: true,
                src: [
                    'lib/tmp/jquery.min.js',                            //  95 kb
                    'lib/tmp/jquery.cookie.min.js',                     //   2 kb
                    'lib/tmp/jquery.formatDateTime.min.js',             //   3 kb
                    'lib/tmp/jquery.tagcloud.min.js',                   //   2 kb
                    'lib/tmp/underscore.min.js',                        //  16 kb
                    'lib/tmp/backbone.min.js',                          //  19 kb
                    'lib/tmp/bootbox.min.js',                           //   9 kb
                    'lib/tmp/jquery.magnific-popup.min.js',             //  21 kb
                    'lib/jquery-ui-1.10.3/jquery-ui.custom.min.js',     //  96 kb
                    'lib/tmp/bootstrap-file-input.min.js',              //   1 kb
                    'lib/tmp/jquery-hotkeys.min.js',                    //   2 kb
                    'lib/tmp/jquery-watchchanges.min.js',               //   1 kb
                    'lib/tmp/jquery-iframe-transport.min.js',           //   2 kb
                    'lib/tmp/jquery-fileupload.min.js',                 //  15 kb
                    'lib/tmp/bootstrap.min.js',                         //   2 kb
                    'lib/select2/select2.min.js',                       //  66 kb
                    'lib/tmp/moment.min.js',                            //  35 kb
                    'lib/tmp/modernizr-custom.min.js'                   //   5 kb
                ],
                dest: 'js/lib.min.js'
            }
        },

        /*
         * CSSMIN: Compress CSS files
         */
        cssmin: {
            installLibCss: {
                options: {
                    compatibility: 'ie8',
                    relativeTo: 'css/',
                    target: 'css/'
                },
                files: {
                    'css/lib.css': [
                        'lib/jquery-ui-1.10.3/jquery-ui.custom.min.css',            // 20 kb
                        'bower_components/magnific-popup/dist/magnific-popup.css',  //  9 kb
                        'lib/select2/select2.css',                                  // 19 kb
                        'lib/jquery-fileupload/jquery-fileupload-ui.css'            //  2 kb
                    ]
                }
            }
        },

        /*
         * UGLIFY: Minify files with UglifyJS
         */
        uglify: {
            prepareLibJs: {
                options: {
                    preserveComments: 'some',
                    sourceMap: true,
                    sourceMapIncludeSources: true
                },
                files: [{
                    expand: true,
                    flatten: true,
                    ext: '.min.js',
                    extDot: 'last',
                    src: [
                        'lib/bootstrap-file-input/bootstrap-file-input.js',
                        'lib/jquery-fileupload/jquery-fileupload.js',
                        'lib/jquery-fileupload/jquery-iframe-transport.js',
                        'lib/jquery-hotkeys/jquery-hotkeys.js',
                        'lib/jquery-watchchanges/jquery-watchchanges.js',
                        'lib/tmp/modernizr-custom.js',
                        'bower_components/jquery/dist/jquery.js',
                        'bower_components/jquery.cookie/jquery.cookie.js',
                        'bower_components/jquery.formatDateTime/jquery.formatDateTime.js',
                        'bower_components/jquery.tagcloud.js/jquery.tagcloud.js',
                        'bower_components/bootbox.js/bootbox.js',
                        'bower_components/magnific-popup/dist/jquery.magnific-popup.js',
                        'bower_components/underscore/underscore.js',
                        'bower_components/backbone/backbone.js',
                        'bower_components/moment/moment.js'
                    ],
                    dest: 'lib/tmp'
                }]
            },
            installCodeMirror: {
                options: {
                    preserveComments: 'some'
                },
                files: [{
                    expand: true,
                    ext: '.min.js',
                    cwd: 'lib/codemirror',
                    src: [
                        'clike.js',
                        'css.js',
                        'htmlmixed.js',
                        'javascript.js',
                        'markdown.js',
                        'matchbrackets.js',
                        'php.js',
                        'xml.js',
                        'yaml.js'
                    ],
                    dest: 'js/ckeditor/plugins/codemirror/plugins'
                }]
            },
            installLocaleDatepicker: {
                options: {
                    preserveComments: 'some'
                },
                files: [{
                    expand: true,
                    ext: '.min.js',
                    cwd: 'lib/datepicker',
                    src: '*.js',
                    dest: 'js/locale/datepicker',
                    rename: function (destBase, destPath) {
                        return destBase + '/' + destPath.replace('datepicker-', '').replace('-', '_');
                    }
                }]
            },
            installLocaleMoment: {
                options: {
                    preserveComments: 'some'
                },
                files: [{
                    expand: true,
                    ext: '.min.js',
                    cwd: 'bower_components/moment/locale',
                    src: '*.js',
                    dest: 'js/locale/moment',
                    rename: function (destBase, destPath) {
                        return destBase + '/' + destPath.replace(/([a-z]+)-([a-z]+)/, function (_, a, b) {
                            return a + '_' + b.toUpperCase();
                        });
                    }
                }]
            },
            prepareBootstrapJs: {
                options: {
                    sourceMap: true,
                    sourceMapIncludeSources: true
                },
                files: {
                    'lib/tmp/bootstrap.min.js': [
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
            boltJs: {
                options: {
                    banner: "/**\n" +
                            " * These are Bolt's COMPILED JS files!\n" +
                            " * You can edit files in <lib/bolt/*.js> and run 'grunt' to generate this file.\n" +
                            " */",
                    sourceMap: true,
                    sourceMapName: 'js/maps/bolt.min.js.map'
                },
                files: {
                    'js/bolt.min.js': ["<%= filesBoltJs %>"]
                }
            }
        },

        /*
         * JSHINT: Validates files with JSHint
         */
        jshint: {
            boltJs: {
                options: {
                    browser: true,      // Defines globals exposed by modern browsers
                    curly: true,        // Always put curly braces around blocks
                    devel: true,        // Defines globals that are usually used for logging/debugging
                    immed: true,        // Prohibits the use of immediate function invocations without parentheses
                    indent: 4,          // Tab width
                    latedef: true,      // Prohibits the use of a variable before it was defined
                    maxlen: 120,        // Maximum length of a line
                    noarg: true,        // Prohibits the use of arguments.caller and arguments.callee
                    nonbsp: true,       // Warns about "non-breaking whitespace" characters
                    singleGroups: true, // Prohibits the use of the grouping operator for single-expression statements
                    undef: true,        // Prohibits the use of undeclared variables
                    globals: {
                        // Bolt
                        bolt: true,                 // bolt/console.js
                        FilelistHolder: true,       // bolt/upload-files.js
                        Files: true,                // bolt/obj-files.js
                        Folders: true,              // bolt/obj-folders.js
                        init: true,                 // bolt/init.js
                        Moments: true,              // bolt/obj-moments.js
                        Navpopups: true,            // bolt/obj-navpopups.js
                        Sidebar: true,              // bolt/obj-sidebar.js
                        Stack: true,                // bolt/obj-stack.js
                        site: true,                 // bolt/extend.js/extend.twig
                        baseurl: true,              // bolt/extend.js/extend.twig
                        rootpath: true,             // bolt/extend.js/extend.twig
                        // Bolt global functions
                        bindFileUpload: true,       // bolt/bindfileuploads.js
                        bindGeolocation: true,      // bolt/geolocation.js
                        bindVideoEmbed: true,       // bolt/video-embed.js
                        getSelectedItems: true,     // bolt/fnc-helpers.js
                        makeUri: true,              // bolt/make-uri-slug.js
                        makeUriAjax: true,          // bolt/make-uri-slug.js
                        stopMakeUri: true,          // bolt/make-uri-slug.js
                        updateLatestActivity: true, // bolt/activity.js
                        validateContent: true,      // bolt/fnc-helpers.js
                        // Vendor
                        $: true,                    // jQuery
                        _: true,                    // underscore.js
                        Backbone: true,             // backbone.min.js
                        bootbox: true,              // bootbox.min.js
                        CKEDITOR: true,             // ckeditor.js
                        CodeMirror: true,           // ckeditor.js
                        google: true,               // Google
                        jQuery: true,               // jQuery
                        moment: true                // moment.min.js
                    }
                },
                src: ["<%= filesBoltJs %>"]
            }
        },

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

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('libcssimg', 'Copy lib images & rebase urls', function() {
        var css = grunt.file.read('css/lib.css'),
            out = css,
            urls = /url\((['"]?)(.+?)\1\)/g,
            repl = {
                'jquery-ui':    /^\.\.\/lib\/jquery-ui-.+?\/images\/ui-/,
                'select2':      /^\.\.\/lib\/select2\//,
                'jquery-upl':   /^\.\.\/lib\/jquery-fileupload\/img\//
            },
            done = {},
            url,
            dest,
            to;

        grunt.file.mkdir('img/lib');

        while ((url = urls.exec(css)) !== null) {
            for (to in repl) {
                dest = url[2].replace(repl[to], 'img/lib/' + to + '-');
                if (dest !== url[2]) {
                    if (!done[dest]) {
                        grunt.file.copy(url[2].replace(/^\.\.\/lib\//, 'lib/'), dest);
                        out = out.replace(
                            new RegExp(url[0].replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), 'g'),
                            'url(' + url[1] + '../' + dest + url[1] + ')'
                        );
                        done[dest] = 1;
                    }
                    break;
                } else {
                    dest = false;
                }
            }
            if (!dest && !url[2].match(/^data:/)) {
                grunt.fail.warn('URL not handled: ' + url[2]);
            }
        }
        grunt.file.write('css/lib.css', out);
    });

    /*** DEFAULT TASK:  Watches for changes of Bolts own css and js files ***/
    grunt.registerTask(
        'default',
        [
            'watch'
        ]
    );

    /*** UPDATE BOLT TASK:  Creates Bolts own css and js files ***/
    grunt.registerTask(
        'updateBolt',
        [
            'sass:'      + 'boltCss',
            'jshint:'    + 'boltJs',
            'uglify:'    + 'boltJs'
        ]
    );

    /*** UPDATE LIB TASK:  Builds library css/js. Run after one of the externals is updated ***/
    grunt.registerTask(
        'updateLib',
        [
            // Prepare
            'uglify:'    + 'prepareBootstrapJs',        // Concat bootstrap scripts into one minified file
            'modernizr:' + 'prepare',                   // Build Modernizr
            'uglify:'    + 'prepareLibJs',              // Create min. versions of library scripts that don't have them
            'remove:'    + 'prepareCkeditor',           // Remove unneeded direcories from downloaded ckeditor
            'bom:'       + 'prepareCkeditor',           // Remove unneeded bom from downloaded ckeditor
            'eol:'       + 'prepareCkeditor',           // Convert CRLF to LF from downloaded ckeditor
            'endline:'   + 'prepareCkeditor',           // Add newlines to *.js of downloaded ckeditor
            // Install
            'copy:'      + 'installFonts',              // Copies fonts                   => view/fonts/*
            'cssmin:'    + 'installLibCss',             // Concats and min. library css   => view/css/lib.css
            'libcssimg:' + '',                          // Copy lib images & rebase urls  => view/img/lib/*
            'concat:'    + 'installLibJs',              // Concats min. library scripts   => view/js/lib.min.js
            'uglify:'    + 'installLocaleDatepicker',   // Copies min. datepicker locale  => view/js/locale/datepicker/*
            'uglify:'    + 'installLocaleMoment',       // Copies min. moment.js locale   => view/js/locale/moment/*
            'copy:'      + 'installCkeditor1',          // Copies CKEditor files          => view/js/ckeditor/*
            'copy:'      + 'installCkeditor2',          // Copies modified ckeditor.js    => view/js/ckeditor/ckeditor.js
            'copy:'      + 'installJqueryGomap',        // Copies jquery-gomap.min.js     => view/js/jquery-gomap.min.js
            'uglify:'    + 'installCodeMirror',         // Copies CodeMirror locale       => view/js/codemirror/*
            // Cleanup
            'remove:'    + 'cleanupTmp'                 // Clean up the tmp folder lib/tmp/
        ]
    );
};
