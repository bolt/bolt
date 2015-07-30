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
        src: '<%= files.boltJs %>'
    }
};
