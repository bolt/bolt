/*
 * ESLINT: Validates files with ESLint
 */
module.exports = {
    /*
     * TARGET:  Checks Bolts js files
     */
    boltJs: {
        options: {
            configFile: 'js/.eslintrc.js'
        },
        nonull: true,
        src: '<%= files.boltJs %>'
    },

    /*
     * TARGET:  Checks grunt js files
     */
    grunt: {
        options: {
            configFile: '.eslintrc.js'
        },
        src: [
            'Gruntfile.js',
            'grunt/**.js',
            'grunt-tasks/**.js'
        ]
    }
};
