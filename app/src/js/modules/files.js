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
    'use strict';

    /**
     * Bolt.files mixin container.
     *
     * @private
     * @type {Object}
     */
    var files = {};

    /**
     * Perform an AJAX POST.
     *
     * @private
     * @static
     * @function exec
     * @memberof Bolt.files
     *
     * @param {type} cmd - Command to send to the async controller.
     * @param {type} data - Request data.
     * @param {type} errMsg - Error message to print on the console when something goes wrong.
     * @param {function} [success] - Callback on success. Defaults to a page relaod.
     */
    function exec(cmd, data, errMsg, success) {
        var options = {
            url: bolt.conf('paths.async') + cmd,
            type: 'POST',
            data: data,
            success: function () {
                document.location.reload();
            },
            error: function (result) {
                alert(result.responseText);
                console.log(errMsg);
            }
        };
        if (success) {
            options.success = success;
        }
        $.ajax(options);
    }

    /**
     * Create a file on the server utilizing an AJAX request.
     *
     * @static
     * @function createFile
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to rename.
     */
    files.createFile = function (namespace, parentPath)
    {
        var fileName = window.prompt(bolt.data('files.msg.create_file'));

        if (fileName.length) {
            exec(
                'file/create',
                {
                    filename: fileName,
                    parentPath: parentPath,
                    namespace: namespace
                },
                'Something went wrong creating this file!'
            );
        }
    };

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
     */
    files.renameFile = function (namespace, parentPath, name)
    {
        var newName = window.prompt(bolt.data('files.msg.rename_file'), name);

        if (newName.length && newName !== name) {
            exec(
                'file/rename',
                {
                    namespace: namespace,
                    parent: parentPath,
                    oldname: name,
                    newname: newName
                },
                'Something went wrong renaming this file!'
            );
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
            exec(
                'file/delete',
                {
                    namespace: namespace,
                    filename: filename
                },
                'Failed to delete the file from the server',
                function () {
                    // If we are on the files table, remove image row from the table, as visual feedback
                    if (element !== null) {
                        $(element).closest('tr').slideUp();
                    }
                    // TODO: Delete from Stack if applicable
                }
            );
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
        exec(
            'file/duplicate',
            {
                namespace: namespace,
                filename: filename
            },
            'Something went wrong duplicating this file!'
        );
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
     */
    files.createFolder = function (namespace, parentPath)
    {
        var newName = window.prompt(bolt.data('files.msg.create_folder'));

        if (newName.length) {
            exec(
                'folder/create',
                {
                    parent: parentPath,
                    foldername: newName,
                    namespace: namespace
                },
                'Something went wrong renaming this folder!'
            );
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
     */
    files.renameFolder = function (namespace, parentPath, name)
    {
        var newName = window.prompt(bolt.data('files.msg.rename_folder'), name);

        if (newName.length && newName !== name) {
            exec(
                'folder/rename',
                {
                    namespace: namespace,
                    parent: parentPath,
                    oldname: name,
                    newname: newName
                },
                'Something went wrong renaming this folder!'
            );
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
     * @param {string} name - Name of the folder to remove.
     */
    files.deleteFolder = function (namespace, parentPath, name) {
        if (window.confirm(bolt.data('files.msg.delete_folder', {'%FOLDERNAME%': name}))) {
            exec(
                'folder/remove',
                {
                    namespace: namespace,
                    parent: parentPath,
                    foldername: name
                },
                'Something went wrong renaming this folder!'
            );
            $.ajax({
                success: function () {
                }
            });
        }
    };

    // Apply mixin container
    bolt.files = files;

})(Bolt || {}, jQuery);
