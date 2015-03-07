/*
 * EOL: Convert line endings
 */
module.exports = function(grunt, options) {
    return {
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
};
