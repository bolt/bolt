/*
 * ENDLINE: Adds a newline at end of a file
 */
module.exports = function(grunt, options) {
    return {
        prepareCkeditor: {
            options: {
                replaced: true
            },
            src: [
                'lib/ckeditor/**/*.js'
            ],
            dest: false
        }
    };
};
