/*
 * REMOVE: Remove directory and files
 */
module.exports = {
    prepareCkeditor: {
        dirList: [
            '<%= path.src.lib %>/ckeditor/adapters',
            '<%= path.src.lib %>/ckeditor/samples'
        ]
    },

    cleanupTmp: {
        dirList: [
            '<%= path.tmp %>'
        ]
    }
};
