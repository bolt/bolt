/*
 * EOL: Convert line endings
 */
module.exports = {
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
};
