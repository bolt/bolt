
jQuery(function ($) {
    // Get configuration
    bolt = $('script[data-config]').first().data('config');

    // Initialize objects
    bolt.files = new Files();
    bolt.folders = new Folders();
    bolt.stack = new Stack();
    bolt.sidebar = new Sidebar();
    bolt.imagelist = [];
    bolt.filelist = [];

    // Initialisation
    init.ckeditor();
    init.confirmationDialogs();
    init.magnificPopup();
    init.dataActions();
    window.setTimeout(function () {
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
    init.sortables();
    init.omnisearch();
    init.uploads();
    init.geolocation();

    $('input[data-bind]').each(function () {
        var data = $(this).data('bind');
        switch (data.bind) {
            case 'date': init.bindDate(data); break;
            case 'datetime': init.bindDateTime(data); break;
            case 'slug': init.bindSlug(data); break;
            default: console.log('Bind ' + data.bind + 'failed!');
        }
    });
});
