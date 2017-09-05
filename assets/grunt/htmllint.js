/*
 * HTMLLINT: html validation using the vnu.jar markup checker.
 */
module.exports = function (grunt, options) {
    'use strict';

    var conf = {};
    // Override settings
    require('deep-extend')(conf, options.htmllint);

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
