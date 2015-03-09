/*
 * ENDLINE: Adds a newline at end of a file
 */
module.exports = {
    prepareCkeditor: {
        options: {
            replaced: true
        },
        src: [
            '<%= path.src.lib %>/ckeditor/**/*.js'
        ],
        dest: false
    }
};
