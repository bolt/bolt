/*
 * POSTCSS: Transforming CSS with JS plugins
 */
module.exports = function (grunt) {
    var proc = {
            autoprefixer: require('autoprefixer-core'),
            cssMqPacker: require('css-mqpacker'),
            csswring: require('csswring'),
            singleCharset: require('postcss-single-charset')
        },
        opt = {
            autoprefixer: {
                browsers: 'last 2 versions, > 5%, IE >= 9'
            }
        };


    return {
        /*
         * TARGET:  Postprocess Bolts css files
         */
        boltCss: {
            options: {
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
