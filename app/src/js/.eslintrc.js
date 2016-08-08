module.exports = {
    "env": {
        "browser": true,    // Define browser global variables
        "jquery":  true     // Define jQuery global variables
    },
    "globals": {
        "Bolt":       true, // Global Bolt object
        "CKEDITOR":   true, // CKeditor
        "CodeMirror": true, // CodeMirror
        "init":       true, // Legacy bolt internal global variable
        "Modernizr":  true, // Modernizr
        "bootbox":    true, // Bootbox.js
        "google":     true, // Google (Geolocation)
        "moment":     true, // Moment.js (deprecated)
        "UIkit":      true  // UIkit
    },
    "extends": "eslint:recommended",
    "rules": {
        /*** Possible Errors ***/

        // Disallow unnecessary parentheses
        "no-extra-parens": "off",
        // Disallow the use of console
        "no-console": "warn",

        /*** Best Practices ***/

        // Enforce consistent brace style for all control statements
        "curly": "error",
        // Require the use of === and !==
        "eqeqeq": "error",
        //equire for-in loops to include an if statement
        "guard-for-in": "error",
        // Require parentheses around immediate function invocations
        "wrap-iife": ["error", "any"],
        // Disallow the use of arguments.caller or arguments.callee
        "no-caller": "error",
        // Disallow comma operators
        "no-sequences": "error",
        // Disallow new operators outside of assignments or comparisons
        "no-new": "error",

        /*** Strict Mode ***/

        /*** Variables ***/

        // Disallow the use of variables before they are defined
        "no-use-before-define": "error",
        // Disallow the use of undeclared variables unless mentioned in /*global */ comments
        "no-undef": "error",
        // Disallow unused variables
        "no-unused-vars": ["error", {"varsIgnorePattern": "^(Bolt|init)$"}],

        /*** Stylistic Issues ***/

        // Enforce consistent indentation
        "indent": ["error", 4, {"SwitchCase": 1}],
        // Enforce a maximum line length
        "max-len": ["warn", {"code": 120, "ignoreComments": true}],
    }
};
