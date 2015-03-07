/*
 * REMOVE: Remove directory and files
 */
module.exports = function(grunt, options) {
    return {
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
};
