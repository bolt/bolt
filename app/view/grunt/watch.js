/*
 * WATCH: Run predefined tasks whenever watched file patterns are added, changed or deleted
 */
module.exports = function(grunt, options) {
    return {
        options: {
            spawn: false,
            livereload: true
        },

        boltCss: {
            files: [
                'sass/**/*.scss'
            ],
            tasks: [
                'sass:boltCss'
            ]
        },

        boltJs: {
            files: options.filesBoltJs,
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
};
