/* global module */

/*
 * MODERNIZR: Modernizr builder
 */
module.exports = {
    /*
     * TARGET:  Build custom Modernizr
     */
    prepare: {
        devFile: 'remote',
        outputFile: '<%= path.tmp %>/modernizr-custom.js',
        extra: {
            touch: true,
            shiv: true,
            cssclasses: true,
            load: false
        },
        extensibility: {
            teststyles: true,
            prefixes: true
        },
        tests: [
            'cookies',
            'contenteditable'
        ],
        uglify: false,
        matchCommunityTests: true,
        parseFiles: false
    }
};
