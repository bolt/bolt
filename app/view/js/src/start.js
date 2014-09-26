
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

    $('[data-bind]').each(function () {
        var data = $(this).data('bind');
        console.log('Binding: ' + data.bind);

        switch (data.bind) {
            case 'date': init.bindDate(data); break;
            case 'datetime': init.bindDateTime(data); break;
            case 'editfile': init.bindEditFile(data); break;
            case 'editlocale': init.bindEditLocale(data); break;
            case 'ckfileselect': init.bindCkFileSelect(); break;
            case 'slug': init.bindSlug(data); break;
            case 'video': init.bindVideo(data); break;
            default: console.log('Binding ' + data.bind + ' failed!');
        }
    });
});
