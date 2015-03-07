/*
 * COPY: Copy files and folders
 */
module.exports = function(grunt, options) {
    return {
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
    };
};
