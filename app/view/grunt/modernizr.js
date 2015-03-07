/*
 * MODERNIZR: Modernizr builder
 */
module.exports = {
    prepare: {
        devFile: "remote",
        outputFile: "lib/tmp/modernizr-custom.js",
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
            'cookies'
        ],
        uglify: false,
        matchCommunityTests: true,
        parseFiles: false
    }
};
