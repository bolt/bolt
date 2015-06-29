/* global module */

/*
 * JSHINT: Validates files with JSHint
 */
module.exports = {
    /*
     * TARGET:  Checks Bolts js files
     */
    boltJs: {
        options: {
            browser: true,      // Defines globals exposed by modern browsers
            curly: true,        // Always put curly braces around blocks
            devel: true,        // Defines globals that are usually used for logging/debugging
            immed: true,        // Prohibits the use of immediate function invocations without parentheses
            indent: 4,          // Tab width
            latedef: true,      // Prohibits the use of a variable before it was defined
            maxlen: 120,        // Maximum length of a line
            noarg: true,        // Prohibits the use of arguments.caller and arguments.callee
            nonbsp: true,       // Warns about "non-breaking whitespace" characters
            singleGroups: false, // Prohibits the use of the grouping operator for single-expression statements
            undef: true,        // Prohibits the use of undeclared variables
            globals: {
                // Bolt
                Bolt: true,                 // bolt.js
                bolt: true,                 // bolt/console.js
                FilelistHolder: true,       // bolt/upload-files.js
                Files: true,                // bolt/obj-files.js
                Folders: true,              // bolt/obj-folders.js
                init: true,                 // bolt/init.js
                Moments: true,              // bolt/obj-moments.js
                Stack: true,                // bolt/obj-stack.js
                site: true,                 // bolt/extend.js/extend.twig
                baseurl: true,              // bolt/extend.js/extend.twig
                rootpath: true,             // bolt/extend.js/extend.twig
                // Bolt global functions
                bindFileUpload: true,       // bolt/bindfileuploads.js
                getSelectedItems: true,     // bolt/fnc-helpers.js
                validateContent: true,      // bolt/fnc-helpers.js
                // Vendor
                $: true,                    // jQuery
                _: true,                    // underscore.js
                Backbone: true,             // backbone.min.js
                bootbox: true,              // bootbox.min.js
                CKEDITOR: true,             // ckeditor.js
                CodeMirror: true,           // ckeditor.js
                google: true,               // Google
                jQuery: true,               // jQuery
                moment: true,               // moment.min.js
                Modernizr: true             // modernizr.min.js
            }
        },
        src: '<%= files.boltJs %>'
    }
};
