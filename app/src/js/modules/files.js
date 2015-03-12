/**
 * Offers file/folder actions (create, delete, duplicate, rename) functionality utilizing AJAX requests.
 *
 * @mixin
 * @namespace Bolt.files
 *
 * @param {Object} bolt - The Bolt module
 * @param {Object} $ - jQuery
 */
(function (bolt, $) {
    /**
     * Bolt.files mixin container.
     *
     * @private
     * @type {Object}
     */
    var files = {};

    bolt.files = files;

    /**
     * Rename a file on the server utilizing an AJAX request.
     *
     * @static
     * @function renameFile
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to rename.
     * @param {string} name - Old name of the file to be renamed.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.renameFile = function (namespace, parentPath, name, element)
    {
        var newName = window.prompt(bolt.data('files.msg.rename_file'), name);

        if (newName.length && newName !== name) {
            $.ajax({
                url: bolt.conf('paths.async') + 'renamefile',
                type: 'POST',
                data: {
                    namespace: namespace,
                    parent: parentPath,
                    oldname: name,
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
    };

    /**
     * Delete a file on the server utilizing an AJAX request.
     *
     * @static
     * @function deleteFile
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} filename - The filename.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.deleteFile = function (namespace, filename, element)
    {
        if (confirm(bolt.data('files.msg.delete_file', {'%FILENAME%': filename}))) {
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
    };


    /**
     * Duplicates a file on the server utilizing an AJAX request.
     *
     * @static
     * @function duplicateFile
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} filename - The filename.
     */
    files.duplicateFile = function (namespace, filename) {
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
    };

    /**
     * Create a folder on the server utilizing an AJAX request.
     *
     * @static
     * @function createFolder
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to create.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.createFolder = function (namespace, parentPath, element)
    {
        var newName = window.prompt(bolt.data('files.msg.create_folder'));

        if (newName.length) {
            $.ajax({
                url: bolt.conf('paths.async') + 'folder/create',
                type: 'POST',
                data: {
                    parent: parentPath,
                    foldername: newName,
                    namespace: namespace
                },
                success: function (result) {
                    document.location.reload();
                },
                error: function () {
                    console.log('Something went wrong renaming this folder!');
                }
            });
        }
    };

    /**
     * Rename a folder on the server utilizing an AJAX request.
     *
     * @static
     * @function renameFolder
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to rename.
     * @param {string} name - Old name of the folder to be renamed.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.renameFolder = function (namespace, parentPath, name, element)
    {
        var newName = window.prompt(bolt.data('files.msg.rename_folder'), name);

        if (newName.length && newName !== name) {
            $.ajax({
                url: bolt.conf('paths.async') + 'folder/rename',
                type: 'POST',
                data: {
                    namespace: namespace,
                    parent: parentPath,
                    oldname: name,
                    newname: newName
                },
                success: function (result) {
                    document.location.reload();
                },
                error: function () {
                    console.log('Something went wrong renaming this folder!');
                }
            });
        }
    };

    /**
     * Deletes a folder on the server utilizing an AJAX request.
     *
     * @static
     * @function deleteFolder
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to remove.
     * @param {string} folderName - Name of the folder to remove.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.deleteFolder = function (namespace, parentPath, folderName, element) {
        if (window.confirm(bolt.data('files.msg.delete_folder', {'%FOLDERNAME%': folderName}))) {
            $.ajax({
                url: bolt.conf('paths.async') + 'folder/remove',
                type: 'POST',
                data: {
                    namespace: namespace,
                    parent: parentPath,
                    foldername: folderName
                },
                success: function (result) {
                    document.location.reload();
                },
                error: function () {
                    console.log('Something went wrong renaming this folder!');
                }
            });
        }
    };
})(Bolt || {}, jQuery);
