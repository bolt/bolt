/*
 * MODERNIZR: Modernizr builder
 */
module.exports = {
    /*
     * TARGET:  Build custom Modernizr
     */
    prepare: {
        dest: '<%= path.tmp %>/modernizr-custom.js',
        options: {
            minify: false,
            classPrefix: 'modernizr-',
            options: [
                'html5shiv',    // Enables HTML5 sectioning elements in IE9
                'setClasses'
            ],
            "feature-detects": [
                'test/contenteditable',
                'test/cookies'
            ]
        }
    }
};
