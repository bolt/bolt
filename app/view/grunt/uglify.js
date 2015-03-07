/*
 * UGLIFY: Minify files with UglifyJS
 */
module.exports = function(grunt, options) {
    return {
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
                'js/bolt.min.js': options.filesBoltJs
            }
        }
    };
};
