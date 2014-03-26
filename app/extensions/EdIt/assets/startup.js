/**
 * Startup
 */
$(function() {
    $('editable').raptor({
        plugins: {
            // Define which save plugin to use. May be saveJson or saveRest
            save: {
                plugin: 'saveJson'
            },
            // Provide options for the saveJson plugin
            saveJson: {
                // The URL to which Raptor data will be POSTed
                url: '/edit/saveit',
                // The parameter name for the posted data
                postName: 'edit-content',
                // A string or function that returns the identifier for the Raptor instance being saved
                id: function() {
                    return this.raptor.getElement().data('content_id'); // slug
                }
            }
        }
    });
});
