/**
 * Initialize keyboard shortcuts:
 * - Click 'save' in Edit content screen.
 * - Click 'save' in "edit file" screen.
 *
 */
function initKeyboardShortcuts() {
    // We're on a regular 'edit content' page, if we have a sidebarsavecontinuebutton.
    // If we're on an 'edit file' screen,  we have a #saveeditfile
    if ($('#sidebarsavecontinuebutton').is('*') || $('#saveeditfile').is('*')) {

        // Bind ctrl-s and meta-s for saving..
        $('body, input').bind('keydown.ctrl_s keydown.meta_s', function(event) {
            event.preventDefault();
            $('form').watchChanges();
            $('#sidebarsavecontinuebutton, #saveeditfile').trigger('click');
        });

        // Initialize watching for changes on "the form".
        window.setTimeout(function() {
            $('form').watchChanges();
        }, 1000);

        function confirmExit() {
            if ($('form').hasChanged()) {
                return "You have unfinished changes on this page. If you continue without saving, you will lose these changes.";
            }
        }

        // Initialize handler for 'closing window'
        window.onbeforeunload = confirmExit;
    }
}
