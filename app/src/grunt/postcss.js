/*
 * POSTCSS: Transforming CSS with JS plugins
 */
module.exports = function (grunt, options) {
    'use strict';

    var proc = {
            autoprefixer: require('autoprefixer'),
            cssMqPacker: require('css-mqpacker'),
            csswring: require('csswring'),
            singleCharset: require('postcss-single-charset')
        },
        opt = {
            autoprefixer: {
                browsers: 'last 2 versions, > 5%, IE >= 9'
            }
        },
        optMap = false;

    // Set options for sourcemap creation.
    if (options.sourcemap.css) {
        optMap = {
            inline: false,
            sourcesContent: true,
            prev: true,
            annotation: options.path.sourcemaps
        };
    }

    // Return the config object.
    return {
        /*
         * TARGET:  Postprocess Bolts css files
         */
        boltCss: {
            options: {
                map: optMap,
                processors: [
                    proc.autoprefixer(opt.autoprefixer),
                    proc.cssMqPacker.postcss,
                    proc.csswring.postcss
                ]
            },
            src:  [
                '<%= path.dest.css %>/bolt.css',
                '<%= path.dest.css %>/liveeditor.css'
            ]
        },

        /*
         * TARGET:  Postprocess libraries css file
         */
        libCss: {
            options: {
                map: optMap,
                processors: [
                    proc.singleCharset.postcss,
                    proc.autoprefixer(opt.autoprefixer),
                    proc.csswring.postcss
                ]
            },
            src:  [
                '<%= path.dest.css %>/lib.css'
            ]
        }
    };
};
