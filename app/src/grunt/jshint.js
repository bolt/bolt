/*
 * JSHINT: Validates files with JSHint
 */
module.exports = {
    /*
     * TARGET:  Checks Bolts js files
     */
    boltJs: {
        options: {
            jshintrc: true
        },
        nonull: true,
        src: '<%= files.boltJs %>'
    },

    /*
     * TARGET:  Checks grunt js files
     */
    grunt: {
        options: {
            jshintrc: true
        },
        src: [
            'Gruntfile.js',
            'grunt/**.js',
            'grunt-tasks/**.js'
        ]
    }
};
