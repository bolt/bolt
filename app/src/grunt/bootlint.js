/* global module */

/*
 * BOOTLINT: HTML linter for Bootstrap projects
 */
module.exports = function (grunt, options) {
    var conf = {
        relaxerror: ['W005'],
        showallerrors: false,
        stoponerror: false,
        stoponwarning: false
    };
    // Override settings
    require('deep-extend')(conf, options.bootlint);

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
