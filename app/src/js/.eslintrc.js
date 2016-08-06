module.exports = {
    "env": {
        "browser": true
    },
    "globals": {
        "$": true,
        "Bolt": true,
        "CKEDITOR": true,
        "CodeMirror": true,
        "init": true,
        "JSON": true,
        "Modernizr": true,
        "bootbox": true,
        "google": true,
        "jQuery": true,
        "moment": true,
        "UIkit": true
    },
    "rules": {
        "curly": "error",
        "eqeqeq": "error",
        "guard-for-in": "error",
        "wrap-iife": ["error","any"],
        "indent": ["error", 4, {"SwitchCase": 1}],
        "no-use-before-define": "error",
        "max-len": ["warn", {"code": 120, "ignoreComments": true}],
        "no-caller": "error",
        "no-sequences": "error",
        "no-irregular-whitespace": "error",
        "no-new": "error",
        "no-extra-parens": "off",
        "no-undef": "error",
        "no-unused-vars": "error"
    }
};
