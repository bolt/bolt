/**
 * Extend String class with a placeholder replacement function
 *
 * Placeholder strings must be surrounded by %, start with an uppercase character, followed by on ore more
 * uppercase characters, numbers or _
 *
 * @param {Array} replacements
 *
 * @returns {String}
 */
String.prototype.subst = function (replacements) {
    "use strict";

    return this.replace(/%[A-Z][A-Z0-9_]+%/g, function (placeholder) {
        return placeholder in replacements ? replacements[placeholder] : placeholder;
    });
};
