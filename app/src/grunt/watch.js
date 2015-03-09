/*
 * WATCH: Run predefined tasks whenever watched file patterns are added, changed or deleted
 */
module.exports = {
    options: {
        spawn: false,
        livereload: true
    },

    boltCss: {
        files: [
            '<%= path.src.sass %>/**/*.scss'
        ],
        tasks: [
            'sass:boltCss'
        ]
    },

    boltJs: {
        files: '<%= files.boltJs %>',
        tasks: [
            'jshint:boltJs',
            'uglify:boltJs'
        ]
    },

    gruntfile: {
        files: [
            'Gruntfile.js'
        ],
        options: {
            reload: true
        }
    }
};
