/*
 * BOM: Byte Order Mark (BOM) removal
 */
module.exports = {
    /*
     * TARGET:  Remove unneeded bom from downloaded CKEditor
     */
    prepareCkeditor: {
        src: [
            '<%= path.src.lib %>/ckeditor/**/*.js'
        ]
    }
};
