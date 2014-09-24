/**
 * Backbone object for all file actions functionality.
 */
var Files = Backbone.Model.extend({

    defaults: {
    },

    initialize: function() {
    },

    /**
     * Rename a file.
     *
     * @param {string} promptQuestionString Translated version of "Which file to rename?".
     * @param {string} namespace            The namespace.
     * @param {string} parentPath           Parent path of the folder to rename.
     * @param {string} oldName              Old name of the file to be renamed.
     * @param {object} element              The object that calls this function, usually of type HTMLAnchorElement)
     */
    renameFile: function(promptQuestionString, namespace, parentPath, oldName, element)
    {
        var newName = window.prompt(promptQuestionString, oldName);

        if (!newName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'renamefile',
            type: 'POST',
            data: {
                namespace: namespace,
                parent:  parentPath,
                oldname: oldName,
                newname: newName
            },
            success: function(result) {
                document.location.reload();
            },
            error: function() {
                console.log('Something went wrong renaming this file!');
            }
        });
    },

    /**
     * Delete a file from the server.
     *
     * @param {string} namespace
     * @param {string} filename
     * @param {object} element
     */
    deleteFile: function(namespace, filename, element) {

        if (!confirm('Are you sure you want to delete ' + filename + '?')) {
            return;
        }

        $.ajax({
            url: asyncpath + 'deletefile',
            type: 'POST',
            data: {
                namespace: namespace,
                filename: filename
            },
            success: function(result) {
                console.log('Deleted file ' + filename  + ' from the server');

                // If we are on the files table, remove image row from the table, as visual feedback
                if (element !== null) {
                    $(element).closest('tr').slideUp();
                }

                // TODO delete from Stack if applicable

            },
            error: function() {
                console.log('Failed to delete the file from the server');
            }
        });
    },

    duplicateFile: function(namespace, filename) {
        $.ajax({
            url: asyncpath + 'duplicatefile',
            type: 'POST',
            data: {
                namespace: namespace,
                filename: filename
            },
            success: function(result) {
                document.location.reload();
            },
            error: function() {
                console.log('Something went wrong duplicating this file!');
            }
        });
    }

});
