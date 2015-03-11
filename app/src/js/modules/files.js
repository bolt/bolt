/*
 * Bolt module: Files
 *
 * Offers file actions functionality.
 *
 * @type {function}
 * @mixin
 */
var BoltFiles = (function (bolt, $) {
    /**
     * Rename a file.
     *
     * @param {string} prompt - Translated version of "Which file to rename?".
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to rename.
     * @param {string} oldName - Old name of the file to be renamed.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement
     */
    function renameFile(prompt, namespace, parentPath, oldName, element)
    {
        console.log('bolt.files.rename');
        var newName = window.prompt(prompt, oldName);

        if (newName.length) {
            $.ajax({
                url: bolt.conf('paths.async') + 'renamefile',
                type: 'POST',
                data: {
                    namespace: namespace,
                    parent: parentPath,
                    oldname: oldName,
                    newname: newName
                },
                success: function (result) {
                    document.location.reload();
                },
                error: function () {
                    console.log('Something went wrong renaming this file!');
                }
            });
        }
    }

    /**
     * Delete a file from the server.
     *
     * @param {string} - The namespace.
     * @param {string} - The filename.
     * @param {Object} - The object that calls this function, usually of type HTMLAnchorElement
     */
    function deleteFile(namespace, filename, element)
    {
        if (confirm('Are you sure you want to delete ' + filename + '?')) {
            $.ajax({
                url: bolt.conf('paths.async') + 'deletefile',
                type: 'POST',
                data: {
                    namespace: namespace,
                    filename: filename
                },
                success: function (result) {
                    // If we are on the files table, remove image row from the table, as visual feedback
                    if (element !== null) {
                        $(element).closest('tr').slideUp();
                    }
                    // TODO delete from Stack if applicable
                },
                error: function () {
                    console.log('Failed to delete the file from the server');
                }
            });
        }
    }

    /**
     * Delete a file from the server.
     *
     * @param {string} namespace - The namespace.
     * @param {string} filename - The filename.
     */
    function duplicateFile(namespace, filename) {
        $.ajax({
            url: bolt.conf('paths.async') + 'duplicatefile',
            type: 'POST',
            data: {
                namespace: namespace,
                filename: filename
            },
            success: function (result) {
                document.location.reload();
            },
            error: function () {
                console.log('Something went wrong duplicating this file!');
            }
        });
    }

    /*
     * Public interface
     */
    bolt.files = {
        rename: renameFile,
        delete: deleteFile,
        duplicate: duplicateFile
    };

    return bolt;
})(Bolt || {}, jQuery);
