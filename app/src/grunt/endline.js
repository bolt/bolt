/*
 * ENDLINE: Adds a newline at end of a file
 */
module.exports = {
    /*
     * TARGET:  Add newlines to *.js of downloaded CKEditor
     */
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
