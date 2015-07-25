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
     * Geolocation field data.
     *
     * @typedef {Object} GmapsApiState
     * @memberof Bolt.app
     *
     * @property {boolean|undefined} loaded  Values: undefined: initial state; false: pending; true: loaded.
     * @property {Array} queue - Queue of requesters.
     */

    /**
     * Bolt.app mixin container.
     *
     * @private
     * @type {Object}
     */
    var app = {};

    /**
     * Loading state of Google Maps API and queue of objects waiting for it.
     *
     * @private
     * @type {GmapsApiState}
     */
    var gmapsApi = {
        loaded: undefined,
        queue: []
    };

    /**
     * Request loading of Google Maps API.
     *
     * @function initGmapsApi
     * @memberof Bolt.app
     *
     * @param {Object|undefined} obj Object that requests loading or undefined for ready callback.
     */
    app.initGmapsApi = function (obj) {
        // Callback from Google Maps API => loaded.
        if (typeof obj === 'undefined') {
            gmapsApi.loaded = true;
            while (gmapsApi.queue.length) {
                gmapsApi.queue.shift().trigger('bolt:gmapsapi:loaded');
            }
        // Request to load Google Maps API.
        } else {
            // Not loaded yet, require it.
            if (gmapsApi.loaded === undefined) {
                gmapsApi.loaded = false;
                $.getScript('https://maps.google.com/maps/api/js?sensor=false&callback=Bolt.app.initGmapsApi')
                    .fail(function (jqxhr, settings, exception) {
                        console.log('ERROR: Google Maps not loaded!');
                    });
            }
            // Pending: queue the object with the handler.
            if (gmapsApi.loaded === false) {
                gmapsApi.queue.push(obj);
            // Loaded: trigger the event.
            } else if (gmapsApi.loaded === true) {
                obj.trigger('bolt:gmapsapi:loaded');
            }
        }
    };

    /**
     * Initializes BUICs.
     *
     * @function initFields
     * @memberof Bolt.app
     */
    app.initBuic = function () {
        $('.buic-checkbox').each(function () {
            bolt.buic.checkbox.init(this);
        });
        $('.buic-select').each(function () {
            bolt.buic.select.init(this);
        });
    };

    /**
     * Initializes fields.
     *
     * @function initFields
     * @memberof Bolt.app
     */
    app.initFields = function () {
        // Init select2 language.
        $.fn.select2.defaults.set("language", bolt.conf('locale.long').replace('_', '-'));

        // Init fieldsets
        $('[data-bolt-field]').each(function () {
            var type = $(this).data('bolt-field'),
                conf = $(this).data('bolt-fconf');

            switch (type) {
                case 'categories':
                    bolt.fields.categories.init(this, conf);
                    break;

                case 'geolocation':
                    bolt.fields.geolocation.init(this, conf);
                    break;

                case 'meta':
                    bolt.fields.meta.init(this, conf);
                    break;

                case 'relationship':
                    bolt.fields.relationship.init(this, conf);
                    break;

                case 'select':
                    bolt.fields.select.init(this, conf);
                    break;

                case 'slug':
                    bolt.fields.slug.init(this, conf);
                    break;

                case 'tags':
                    bolt.fields.tags.init(this, conf);
                    break;

                case 'templateselect':
                    bolt.fields.templateselect.init(this, conf);
                    break;

                case 'checkbox':
                case 'date':
                case 'datetime':
                case 'file':
                case 'filelist':
                case 'float':
                case 'grouping':
                case 'html':
                case 'image':
                case 'imagelist':
                case 'integer':
                case 'markdown':
                case 'text':
                case 'textarea':
                case 'video':
                    // Not implemented yet.
                    break;

                default:
                    console.log('Unknown field type: ' + type);
            }

        });
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
        bolt.actions.init();
        bolt.secmenu.init();
        bolt.stack.init();
        bolt.omnisearch.init();

        bolt.activity.init();
        bolt.ckeditor.init();
        bolt.datetime.init();

        legacyInit();
        bolt.app.initBuic();
        bolt.app.initFields();
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
        bolt.moments = new Moments();
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
        init.dashboardCheckboxes();
        init.sortables();
        init.uploads();
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

    // Apply mixin container
    bolt.app = app;

})(Bolt || {}, jQuery);
