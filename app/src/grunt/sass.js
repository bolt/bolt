/*
 * SASS: Compile Sass to CSS
 */
module.exports = {
    /*
     * TARGET:  Build Bolts css file
     */
    boltCss: {
        options: {
            includePaths: [
                '<%= path.src.npm %>/bootstrap-sass/assets/stylesheets/',
                '<%= path.src.npm %>/font-awesome/scss/'
            ],
            lineNumbers: false,
            unixNewlines: true,
            banner: '<%= banner.boltCss %>',
            precision: 8,
            sourceMap: '<%= sourcemap.css ? path.sourcemaps + "/" : "" %>',
            sourceMapContents: true
        },
        files: [
            {
                src:  '<%= path.src.sass %>/app.scss',
                dest: '<%= path.dest.css %>/bolt.css'
            }, {
                src:  '<%= path.src.sass %>/liveeditor.scss',
                dest: '<%= path.dest.css %>/liveeditor.css'
            }
        ]
    }
};
