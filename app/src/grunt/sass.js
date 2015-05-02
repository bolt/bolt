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
            sourceMap: '<%= sourcemap.css %>',
            sourceMapContents: true
        },
        files: [
            {
                src:  '<%= path.src.sass %>/app-old-ie.scss',
                dest: '<%= path.dest.css %>/bolt-old-ie.css'
            }, {
                src:  '<%= path.src.sass %>/app.scss',
                dest: '<%= path.dest.css %>/bolt.css'
            }, {
                src:  '<%= path.src.sass %>/liveeditor.scss',
                dest: '<%= path.dest.css %>/liveeditor.css'
            }
        ]
    }
};
