/*
 * COPY: Copy files and folders
 */
module.exports = {
    /*
     * TARGET:  Copies fonts
     */
    installFonts: {
        files: [{
            expand: true,
            flatten: true,
            src: [
                '<%= path.src.node %>/font-awesome/fonts/*'
            ],
            filter: 'isFile',
            dest: '<%= path.dest.fonts %>/'
        }]
    },

    /*
     * TARGET:  Copies CKEditor files
     */
    installCkeditor1: {
        files: [
            {
                // Copy all CKEditor files
                expand: true,
                cwd: '<%= path.src.lib %>/ckeditor',
                src: [
                    'plugins/**',
                    'skins/**',
                    'styles.js'
                ],
                dest: '<%= path.dest.js %>/ckeditor'
            }, {
                // Copy our empty config file
                src: '<%= path.src.js %>/ckeditor-config.js',
                dest: '<%= path.dest.js %>/ckeditor/config.js'
            }, {
                // Copy style to css folder
                src: '<%= path.src.lib %>/ckeditor/contents.css',
                dest: '<%= path.dest.css %>/ckeditor-contents.css'
            }, {
                // Copy CKEditor locale
                expand: true,
                flatten: true,
                src: '<%= path.src.lib %>/ckeditor/lang/*.js',
                dest: '<%= path.dest.js %>/locale/ckeditor'
            }
        ]
    },

    /*
     * TARGET:  Copies modified ckeditor.js
     */
    installCkeditor2: {
        // process doesn't work on file level, so we need a new target
        src: '<%= path.src.lib %>/ckeditor/ckeditor.js',
        dest: '<%= path.dest.js %>/ckeditor/ckeditor.js',
        options: {
            process: function (cont) {
                return cont.replace(/(CKEDITOR\.getUrl\()"lang\/"(\+a\+"\.js"\))/, '$1"../locale/ckeditor/"$2');
            }
        }
    },

    /*
     * TARGET:  Copies jquery-gomap.min.js
     */
    installJqueryGomap: {
        src: '<%= path.src.lib %>/jquery-gomap/jquery-gomap.min.js',
        dest: '<%= path.dest.js %>/jquery-gomap.min.js'
    }
};
