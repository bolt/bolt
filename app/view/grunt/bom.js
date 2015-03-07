/*
 * BOM: Byte Order Mark (BOM) removal
 */
module.exports = function(grunt, options) {
    return {
        prepareCkeditor: {
            src: [
                'lib/ckeditor/**/*.js'
            ]
        }
    };
};
