/**
 * Main mixin for the Bolt module.
 *
 * @mixin
 * @namespace Bolt.app
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Bolt.app mixin container.
     *
     * @private
     * @type {Object}
     */
    var app = {};

    /**
     * Initializes and then starts the Bolt module.
     * Is automatically executed on jQueries ``$(document).ready()``.
     *
     * @function run
     * @memberof Bolt.app
     */
    app.run = function () {
        bolt.conf.init();
        bolt.data.init();
        bolt.stack.init();
        bolt.ckeditor.init();

        bolt.actions.bind();

        legacyInit();
    };

    /*
     * Start when ready.
     */
    $(document).ready(app.run);

    /**
     * Legacy stuff from start.js.
     *
     * @private
     * @static
     * @function legacyInit
     * @memberof Bolt.app
     * @todo Move functionality to Bolt mixins.
     * @deprecated To be removed!
     */
    function legacyInit() {
        // Get passed in data from Twig function data()

        // Initialize objects
        bolt.sidebar = new Sidebar();
        bolt.navpopups = new Navpopups();
        bolt.moments = new Moments();
        bolt.imagelist = [];
        bolt.filelist = [];

        // Initialisation
        bolt.datetimes.init();
        //
        init.confirmationDialogs();
        init.magnificPopup();
        window.setTimeout(function () {
            init.keyboardShortcuts();
        }, 1000);
        init.dropZone();
        init.popOvers();
        init.activityWidget();
        init.dropDowns();
        init.deferredWidgets();
        init.passwordInput();
        init.dashboardCheckboxes();
        init.sortables();
        init.omnisearch();
        init.uploads();
        init.geolocation();
        init.focusStatusSelect();
        init.depublishTracking();

        $('[data-bind]').each(function () {
            var data = $(this).data('bind');
            //console.log('Binding: ' + data.bind);

            switch (data.bind) {
                case 'editcontent': init.bindEditContent(data); break;
                case 'editfile': init.bindEditFile(data); break;
                case 'editlocale': init.bindEditLocale(data); break;
                case 'filebrowser': init.bindFileBrowser(); break;
                case 'ckfileselect': init.bindCkFileSelect(); break;
                case 'prefill': init.bindPrefill(); break;
                case 'slug': init.bindSlug(data); break;
                case 'video': init.bindVideo(data); break;
                default: console.log('Binding ' + data.bind + ' failed!');
            }
        });
    }

    // Apply mixin container
    bolt.app = app;

})(Bolt || {}, jQuery);
