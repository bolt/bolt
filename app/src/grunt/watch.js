/*
 * WATCH: Run predefined tasks whenever watched file patterns are added, changed or deleted
 */
module.exports = {
    options: {
        spawn: false,
        livereload: true
    },

    /*
     * TARGET:  Build Bolts css file changes
     */
    boltCss: {
        files: [
            '<%= path.src.sass %>/**/*.scss'
        ],
        tasks: [
            'sass:boltCss',
            'postcss:boltCss'
        ]
    },

    /*
     * TARGET:  Build Bolts js file changes
     */
    boltJs: {
        files: '<%= path.src.js %>/**/*.js',
        tasks: [
            'eslint:boltJs',
            'uglify:boltJs'
        ]
    },

    /*
     * TARGET:  Watch Gruntfile changes and then reload
     */
    gruntfile: {
        files: [
            'Gruntfile.js',
            'grunt/*.js',
            'grunt/*.yml',
            'grunt-local/*.js'
        ],
        options: {
            reload: true
        },
        tasks: [
            'eslint:grunt'
        ]
    }
};
