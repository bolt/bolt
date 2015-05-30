/*
 * POSTCSS: Transforming CSS with JS plugins
 */
module.exports = function (grunt) {
    var procSingleCharset = require('postcss-single-charset');

    return {
        /*
         * TARGET:  Postprocess Bolts css files
         */
        boltCss: {
            options: {
                processors: [
                ]
            },
            src:  [
                '<%= path.dest.css %>/bolt-old-ie.css',
                '<%= path.dest.css %>/bolt.css',
                '<%= path.dest.css %>/liveeditor.css'
            ]
        },

        /*
         * TARGET:  Postprocess libraries css file
         */
        libCss: {
            options: {
                processors: [
                    procSingleCharset.postcss
                ]
            },
            src:  [
                '<%= path.dest.css %>/lib.css'
            ]
        }
	};
};
