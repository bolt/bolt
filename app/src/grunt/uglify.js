/* global module */

/*
 * UGLIFY: Minify files with UglifyJS
 */
module.exports = {
    /*
     * TARGET:  Create minified versions of library scripts that don't have them
     */
    prepareLibJs: {
        options: {
            preserveComments: 'some',
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
                '<%= path.src.bower %>/blueimp-file-upload/js/jquery.fileupload.js',
                '<%= path.src.bower %>/blueimp-file-upload/js/jquery.iframe-transport.js',
                '<%= path.src.lib %>/jquery-hotkeys/jquery-hotkeys.js',
                '<%= path.tmp %>/modernizr-custom.js',
                '<%= path.src.bower %>/jquery/dist/jquery.js',
                '<%= path.src.bower %>/jquery.cookie/jquery.cookie.js',
                '<%= path.src.bower %>/jquery.formatDateTime/jquery.formatDateTime.js',
                '<%= path.src.bower %>/jquery.tagcloud.js/jquery.tagcloud.js',
                '<%= path.src.bower %>/bootbox/bootbox.js',
                '<%= path.src.bower %>/magnific-popup/dist/jquery.magnific-popup.js',
                '<%= path.src.bower %>/underscore/underscore.js',
                '<%= path.src.bower %>/backbone/backbone.js',
                '<%= path.src.bower %>/moment/moment.js'
            ],
            dest: '<%= path.tmp %>'
        }]
    },

    /*
     * TARGET:  Copies CodeMirror locale
     */
    installCodeMirror: {
        options: {
            preserveComments: 'some'
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
            preserveComments: 'some'
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.bower %>/jquery-ui/ui/i18n',
            src: '*.js',
            dest: '<%= path.dest.js %>/locale/datepicker',
            rename: function (destBase, destPath) {
                return destBase + '/' + destPath.replace('datepicker-', '').replace('-', '_');
            }
        }]
    },

    /*
     * TARGET:  Copies min. moment.js locale
     */
    installLocaleMoment: {
        options: {
            preserveComments: 'some'
        },
        files: [{
            expand: true,
            ext: '.min.js',
            cwd: '<%= path.src.bower %>/moment/locale',
            src: '*.js',
            dest: '<%= path.dest.js %>/locale/moment',
            rename: function (destBase, destPath) {
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
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/button.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/transition.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
                '<%= path.src.bower %>/bootstrap-sass/assets/javascripts/bootstrap/popover.js'
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
        files: {
            '<%= path.dest.js %>/bolt.js': '<%= files.boltJs %>'
        }
    }
};
