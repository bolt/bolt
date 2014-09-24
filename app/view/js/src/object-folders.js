/**
 * This backbone model cares about folder actions within /files in the backend.
 */
var Folders = Backbone.Model.extend({

    defaults: {
    },

    initialize: function() {
    },

    /**
     * Create a folder.
     *
     * @param string promptQuestionString Translated version of "What's the new filename?".
     * @param string parentPath Parent path of the folder to create.
     */
    create: function(promptQuestionString, namespace, parentPath, element)
    {
        var newFolderName = window.prompt(promptQuestionString);

        if (!newFolderName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'folder/create',
            type: 'POST',
            data: {
                parent: parentPath,
                foldername: newFolderName,
                namespace: namespace
            },
            success: function(result) {
                document.location.reload();
            },
            error: function() {
                console.log('Something went wrong renaming this folder!');
            }
        });
    },

    /**
     * Rename a folder.
     *
     * @param string promptQuestionString Translated version of "Which file to rename?".
     * @param string parentPath           Parent path of the folder to rename.
     * @param string oldName              Old name of the folder to be renamed.
     * @param string newName              New name of the folder to be renamed.
     */
    rename: function(promptQuestionString, namespace, parentPath, oldFolderName, element)
    {
        var newFolderName = window.prompt(promptQuestionString, oldFolderName);

        if (!newFolderName.length) {
            return;
        }

        $.ajax({
            url: asyncpath + 'folder/rename',
            type: 'POST',
            data: {
                namespace: namespace,
                parent: parentPath,
                oldname: oldFolderName,
                newname: newFolderName
            },
            success: function(result) {
                document.location.reload();
            },
            error: function() {
                console.log('Something went wrong renaming this folder!');
            }
        });
    },

    /**
     * Remove a folder.
     *
     * @param string parentPath Parent path of the folder to remove.
     * @param string folderName Name of the folder to remove.
     */
    remove: function(namespace, parentPath, folderName, element)
    {
        $.ajax({
            url: asyncpath + 'folder/remove',
            type: 'POST',
            data: {
                namespace: namespace,
                parent: parentPath,
                foldername: folderName
            },
            success: function(result) {
                document.location.reload();
            },
            error: function() {
                console.log('Something went wrong renaming this folder!');
            }
        });
    }
});
