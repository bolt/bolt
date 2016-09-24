/*
 * UGLIFY: Minify files with UglifyJS
 */
module.exports = {
    /*
     * TARGET:  Create minified versions of library scripts that don't have them
     */
    prepareLibJs: {
        options: {
            preserveComments: /(?:^!|@(?:license|preserve|cc_on))/,
            sourceMap: '<%= sourcemap.js %>',
            sourceMapIncludeSources: true
        },
        files: [{
            expand: true,
            flatten: true,
            ext: '.min.js',
            extDot: 'last',
            nonull: true,
            src: [
                '<%= path.src.lib %>/bootstrap-file-input/bootstrap-file-input.js',
                '<%= path.src.npm %>/blueimp-file-upload/js/jquery.fileupload.js',
                '<%= path.src.npm %>/blueimp-file-upload/js/jquery.fileupload-process.js',
                '<%= path.src.npm %>/blueimp-file-upload/js/jquery.fileupload-validate.js',
                '<%= path.src.npm %>/blueimp-file-upload/js/jquery.iframe-transport.js',
                '<%= path.src.lib %>/jquery-hotkeys/jquery-hotkeys.js',
                '<%= path.tmp %>/modernizr-custom.js',
                '<%= path.src.npm %>/jquery/dist/jquery.js',
                '<%= path.src.npm %>/jquery.cookie/jquery.cookie.js',
                '<%= path.src.npm %>/jquery-formatdatetime/jquery.formatDateTime.js',
                '<%= path.src.npm %>/jquery.tagcloud.js/jquery.tagcloud.js',
                '<%= path.src.npm %>/bootbox/bootbox.js',
                '<%= path.src.npm %>/magnific-popup/dist/jquery.magnific-popup.js',
                '<%= path.src.npm %>/moment/moment.js',
                '<%= path.src.lib %>/select2-ext/select2.sortable.js'
            ],
            dest: '<%= path.tmp %>'
        }]
    },

    /*
     * TARGET:  Copies CodeMirror locale
     */
    installCodeMirror: {
        options: {
            preserveComments: /(?:^!|@(?:license|preserve|cc_on))/
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.lib %>/codemirror',
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
            dest: '<%= path.dest.js %>/ckeditor/plugins/codemirror/plugins'
        }]
    },

    /*
     * TARGET:  Copies min. datepicker locale
     */
    installLocaleDatepicker: {
        options: {
            preserveComments: /(?:^!|@(?:license|preserve|cc_on))/
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.npm %>/jquery-ui/ui/i18n',
            src: '*.js',
            dest: '<%= path.dest.js %>/locale/datepicker',
            rename: function (destBase, destPath) {
                'use strict';

                return destBase + '/' + destPath.replace('datepicker-', '').replace('-', '_');
            }
        }]
    },

    /*
     * TARGET:  Copies min. select2 locale
     */
    installLocaleSelect2: {
        options: {
            preserveComments: /(?:^!|@(?:license|preserve|cc_on))/
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.npm %>/select2/dist/js/i18n',
            src: '*.js',
            dest: '<%= path.dest.js %>/locale/select2',
            rename: function (destBase, destPath) {
                'use strict';

                return destBase + '/' + destPath.replace('-', '_');
            }
        }]
    },

    /*
     * TARGET:  Copies min. moment.js locale
     */
    installLocaleMoment: {
        options: {
            preserveComments: /(?:^!|@(?:license|preserve|cc_on))/
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.npm %>/moment/locale',
            src: '*.js',
            dest: '<%= path.dest.js %>/locale/moment',
            rename: function (destBase, destPath) {
                'use strict';

                return destBase + '/' + destPath.replace(/([a-z]+)-([a-z]+)/, function (_, a, b) {
                    return a + '_' + b.toUpperCase();
                });
            }
        }]
    },

    /*
     * TARGET:  Concat bootstrap scripts into one minified file
     */
    prepareBootstrapJs: {
        options: {
            sourceMap: '<%= sourcemap.js %>',
            sourceMapIncludeSources: true
        },
        files: {
            '<%= path.tmp %>/bootstrap.min.js': [
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/button.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/transition.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
                '<%= path.src.npm %>/bootstrap-sass/assets/javascripts/bootstrap/popover.js'
            ]
        }
    },

    /*
     * TARGET:  Build Bolts js file
     */
    boltJs: {
        options: {
            banner: '<%= banner.boltJs %>',
            sourceMap: '<%= sourcemap.js %>',
            sourceMapName: '<%= path.sourcemaps %>/bolt.js.map'
        },
        files: [{
            nonull: true,
            dest: '<%= path.dest.js %>/bolt.js',
            src: '<%= files.boltJs %>'
        }]
    }
};
