/**
 * Offers file actions (delete, duplicate, rename) functionality utilizing AJAX requests.
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
     * @function rename
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} parentPath - Parent path of the folder to rename.
     * @param {string} oldName - Old name of the file to be renamed.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.rename = function (namespace, parentPath, oldName, element)
    {
        var newName = window.prompt(bolt.data('files.rename_msg'), oldName);

        if (newName.length && newName !== oldName) {
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
    };

    /**
     * Delete a file on the server utilizing an AJAX request.
     *
     * @static
     * @function delete
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} filename - The filename.
     * @param {Object} element - The object that calls this function, usually of type HTMLAnchorElement.
     */
    files.delete = function (namespace, filename, element)
    {
        if (confirm(bolt.data('files.delete_msg', {'%FILENAME%': filename}))) {
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
     * @function duplicate
     * @memberof Bolt.files
     *
     * @param {string} namespace - The namespace.
     * @param {string} filename - The filename.
     */
    files.duplicate = function (namespace, filename) {
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
})(Bolt || {}, jQuery);
