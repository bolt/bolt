/**
 * Main mixin for the Bolt module.
 *
 * @mixin
 * @namespace Bolt.app
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} moment - moment.js.
 * @param {Object} init - Bolts deprectated init object.
 */
(function (bolt, $, moment, init) {
    'use strict';

    /**
     * Bolt.app mixin container.
     *
     * @private
     * @type {Object}
     */
    var app = {};

    /**
     * Loading state of Google Maps API.
     *
     * Values: undefined: initial state; false: pending; true: loaded.
     *
     * @private
     * @type {boolean|undefined}
     */
    var gMapsApiLoaded;

    /**
     * Callback that signals that Google Maps API is fully loaded.
     *
     * See: https://developers.google.com/maps/documentation/javascript/tutorial#asynch
     *
     * @function gMapsApiReady
     * @memberof Bolt.app
     */
    app.gMapsApiReady = function () {
        gMapsApiLoaded = true;
        $(bolt).trigger('bolt:gmaps-loaded');
    };

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

        initGlobal();
        initHandler();

        bolt.actions.init();
        bolt.secmenu.init();
        bolt.stack.init();
        bolt.omnisearch.init();
        bolt.extend.init();

        bolt.activity.init();
        bolt.ckeditor.init();
        bolt.datetime.init();

        legacyInit();
        initBuic();
        bolt.fields.init();
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
        bolt.imagelist = [];
        bolt.filelist = [];

        // Initialisation
        init.confirmationDialogs();
        init.magnificPopup();
        init.dropZone();
        init.popOvers();
        init.dropDowns();
        init.deferredWidgets();
        init.passwordInput();
        init.sortables();
        init.focusStatusSelect();
        init.depublishTracking();

        $('[data-bind]').each(function () {
            var data = $(this).data('bind');
            //console.log('Binding: ' + data.bind);

            switch (data.bind) {
                case 'editcontent': bolt.editcontent.init(data); break;
                case 'editfile': init.bindEditFile(data); break;
                case 'editlocale': init.bindEditLocale(data); break;
                case 'filebrowser': init.bindFileBrowser(); break;
                case 'ckfileselect': init.bindCkFileSelect(); break;
                case 'prefill': init.bindPrefill(); break;
                case 'video': bolt.video.bind(data.key); break;
                default: console.log('Binding ' + data.bind + ' failed!');
            }
        });
    }

    /**
     * Initializes Bolts event handler.
     *
     * @private
     * @static
     * @function initHandler
     * @memberof Bolt.app
     */
    function initHandler() {
        $(bolt)
            // Google Maps API loading
            // - bolt:gmaps-load:   request API loading.
            // - bolt:gmaps-loaded: API loaded successfully.
            // - bolt:gmaps-failed: loading failed.
            .on('bolt:gmaps-load', function () {
                if (gMapsApiLoaded === undefined) {
                    // Request loading Google Maps API.
                    gMapsApiLoaded = false;
                    $.getScript('https://maps.google.com/maps/api/js?sensor=false&callback=Bolt.app.gMapsApiReady')
                        .fail(function () {
                            gMapsApiLoaded = undefined;
                            $(bolt).trigger('bolt:gmaps-failed');
                        });
                } else if (gMapsApiLoaded === true) {
                    // Already loaded, signal it.
                    $(bolt).trigger('bolt:gmaps-loaded');
                }
            });
    }

    /**
     * Initializes globals.
     *
     * @private
     * @function initBuic
     * @memberof Bolt.app
     */
    function initGlobal() {
        var localeLong = bolt.conf('locale.long');

        // Init select2 language.
        $.fn.select2.defaults.set('language', localeLong.replace('_', '-'));
        // Set locale of moments.js.
        moment.locale(localeLong);
        // Set global datepicker locale.
        $.datepicker.setDefaults($.datepicker.regional[localeLong]);
    }

    /**
     * Initializes BUICs.
     *
     * @private
     * @function initBuic
     * @memberof Bolt.app
     */
    function initBuic() {
        $('.buic-checkbox').each(function () {
            bolt.buic.checkbox.init(this);
        });
        $('.buic-listing').each(function () {
            bolt.buic.listing.init(this);
        });
        $('.buic-moment').each(function () {
            bolt.buic.moment.init(this);
        });
        $('.buic-progress').each(function () {
            bolt.buic.progress.init(this);
        });
        $('.buic-select').each(function () {
            bolt.buic.select.init(this);
        });
    }

    // Apply mixin container
    bolt.app = app;

})(Bolt || {}, jQuery, moment, init);
