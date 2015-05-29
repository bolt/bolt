/*
 * POSTCSS: Transforming CSS with JS plugins
 */
module.exports = function (grunt) {
	return {
        options: {
            processors: [
            ]
        },

        /*
         * TARGET:  Postprocess Bolts css file
         */
        boltCss: {
            src:  [
                '<%= path.dest.css %>/bolt-old-ie.css',
                '<%= path.dest.css %>/bolt.css',
                '<%= path.dest.css %>/liveeditor.css'
            ]
        }
	};
};
