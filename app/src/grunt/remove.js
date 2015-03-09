/*
 * REMOVE: Remove directory and files
 */
module.exports = {
    prepareCkeditor: {
        dirList: [
            'lib/ckeditor/adapters',
            'lib/ckeditor/samples'
        ]
    },

    cleanupTmp: {
        dirList: [
            'lib/tmp'
        ]
    }
};
