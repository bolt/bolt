/*
 * REMOVE: Remove directory and files
 */
module.exports = {
    /*
     * TARGET:  Remove unneeded direcories from downloaded CKEditor
     */
    prepareCkeditor: {
        dirList: [
            '<%= path.src.lib %>/ckeditor/adapters',
            '<%= path.src.lib %>/ckeditor/samples',
            '<%= path.src.lib %>/ckeditor/skins/moono'
        ]
    },

    /*
     * TARGET:  Empties the tmp folder and removes it
     */
    cleanupTmp: {
        dirList: [
            '<%= path.tmp %>'
        ]
    }
};
