/*
 * BOOTLINT: HTML linter for Bootstrap projects
 */
module.exports = function (grunt) {
    var conf = {
        relaxerror: [],
        showallerrors: false,
        stoponerror: false,
        stoponwarning: false
    };

    return {
        /*
         * TARGET:  Check html files in "path.pages"
         */
        pages: {
            options: conf,
            src: '<%= path.pages %>/*.html'
        }
    };
};
