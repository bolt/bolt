jQuery(function($) {

    init.confirmationDialogs();
    init.magnificPopup();
    init.dataActions();
    window.setTimeout(function() {
        init.keyboardShortcuts();
    }, 1000);
    init.dropZone();
    init.popOvers();
    init.dateTimePickers();
    init.momentTimestamps();
    init.activityWidget();
    init.dropDowns();
    init.deferredWidgets();
    init.passwordInput();
    init.dashboardCheckboxes();
    init.deleteChosenItems();
    init.sortables();
    init.omnisearch();

    // Initialize objects

    files = new Files();
    folders = new Folders();
    stack = new Stack();
    sidebar = new Sidebar();
});
