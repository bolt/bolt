/*
 * SASS: Compile Sass to CSS
 */
module.exports = {
    /*
     * TARGET:  Build Bolts css file
     */
    boltCss: {
        options: {
            outputStyle: 'compressed',
            includePaths: [
                '<%= path.src.node %>/bootstrap-sass/assets/stylesheets/',
                '<%= path.src.node %>/font-awesome/scss/'
            ],
            lineNumbers: false,
            unixNewlines: true,
            banner: '<%= banner.boltCss %>',
            precision: 5,
            sourceMap: true,
            sourceMapContents: true
        },
        files: {
            '<%= path.dest.css %>/bolt-old-ie.css': '<%= path.src.sass %>/app-old-ie.scss',
            '<%= path.dest.css %>/bolt.css': '<%= path.src.sass %>/app.scss'
        }
    }
};
