/*
 * EOL: Convert line endings
 */
module.exports = {
    /*
     * TARGET:  Convert CRLF to LF in downloaded CKEditor
     */
    prepareCkeditor: {
        options: {
            eol: 'lf',
            replace: true
        },
        files: {
            src: [
                '<%= path.src.lib %>/ckeditor/**/*.js',
                '<%= path.src.lib %>/ckeditor/**/*.css',
                '<%= path.src.lib %>/ckeditor/**/*.md',
                '<%= path.src.lib %>/ckeditor/**/*.txt'
            ]
        }
    }
};
