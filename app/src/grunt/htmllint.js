/*
 * HTMLLINT: html validation using the vnu.jar markup checker.
 */
module.exports = {
    /*
     * TARGET:  Check html files in "path.pages"
     */
    pages: {
        options: {
        },
        src: '<%= path.pages %>/*.html'
    }
};
