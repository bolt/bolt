module.exports = {
    "env": {
        "node": true    // Define Node.js global variables and Node.js scoping
    },
    "extends": "eslint:recommended",
    "rules": {
        /*** Possible Errors ***/

        // Disallow irregular whitespace outside of strings and comments
        "no-irregular-whitespace": "error",
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

        // Require or disallow strict mode directives
        "strict": ["error", "function"],

        /*** Variables ***/

        // Disallow the use of variables before they are defined
        "no-use-before-define": "error",
        // Disallow the use of undeclared variables unless mentioned in /*global */ comments
        "no-undef": "error",
        // Disallow unused variables
        "no-unused-vars": "error",

        /*** Node.js and CommonJS ***/

        /*** Stylistic Issues ***/

        // Enforce consistent indentation
        "indent": ["error", 4, {"SwitchCase": 1}],
        // Enforce a maximum line length
        "max-len": ["warn", {"code": 120, "ignoreComments": true}]
    }
};
