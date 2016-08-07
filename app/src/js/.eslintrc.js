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
        "JSON":       true,
        "Modernizr":  true, // Modernizr
        "bootbox":    true, // Bootbox.js
        "google":     true, // Google (Geolocation)
        "moment":     true, // Moment.js (deprecated)
        "UIkit":      true  // UIkit
    },
    "rules": {
        "curly": "error",
        "eqeqeq": "error",
        "guard-for-in": "error",
        "wrap-iife": ["error", "any"],
        "indent": ["error", 4, {"SwitchCase": 1}],
        "no-use-before-define": "error",
        "max-len": ["warn", {"code": 120, "ignoreComments": true}],
        "no-caller": "error",
        "no-sequences": "error",
        "no-irregular-whitespace": "error",
        "no-new": "error",
        "no-extra-parens": "off",
        "no-undef": "error",
        "no-unused-vars": ["error", {"varsIgnorePattern": "^(Bolt|init)$"}]
    }
};
