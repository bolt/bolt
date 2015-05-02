/**
 * Handling of geolocation input fields.
 *
 * @mixin
 * @namespace Bolt.fields.geolocation
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Field configuration.
     *
     * @typedef {Object} FieldConf
     * @memberof Bolt.fields.slug
     *
     * @property {string} key - The field key.
     * @property {string} latitude: - Latitude.
     * @property {string} longitude - Longitude.
     */

    /**
     * Geolocation field data.
     *
     * @typedef {Object} FieldGeolocation
     * @memberof Bolt.fields.slug
     *
     * @property {Object} address - Input: Address lookup.
     * @property {Object} matched - Readonly input: displaying matched address.
     * @property {Object} mapholder - Element containing map.
     * @property {Object} latitude - Input: Latitude.
     * @property {Object} longitude - Input: Longitude.
     * @property {Object} map - Google map object.
     * @property {Object} marker - Map marker.
     * @property {integer} timeout - Timeout for location resolving.
     */

    /**
     * Bolt.fields.geolocation mixin container.
     *
     * @private
     * @type {Object}
     */
    var geolocation = {};

    /**
     * Bind geolocation field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.geolocation
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    geolocation.init = function (fieldset, fconf) {
        var field = {
                address: $(fieldset).find('.address'),
                matched: $(fieldset).find('.matched'),
                mapholder: $(fieldset).find('.mapholder'),
                latitude: $(fieldset).find('.latitude'),
                longitude: $(fieldset).find('.longitude'),
                map: null,
                marker: null,
                timeout: undefined
            };

        if (typeof google !== 'undefined' && google.maps) {
            initGoogleMap(field, fconf.latitude, fconf.longitude);
        } else {
            console.log('ERROR: Google Maps not loaded!');
        }
    };

    /**
     * Geocode address or location and display result
     *
     * @private
     * @function geoCode
     * @memberof Bolt.fields.geolocation
     *
     * @param {FieldGeolocation} field - Field data.
     * @param {Object|undefined} search - Optional address or location search.
     */
    function geoCode(field, search) {
        if (search) {
            (new google.maps.Geocoder()).geocode(
                search,
                function (results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        var location = results[0].geometry.location;

                        display(field, results[0].formatted_address, location.lat(), location.lng());

                        field.marker.setPosition(location);
                        field.map.setCenter(location);
                    }
                }
            );
        }
        display(field);
    }

    /**
     * Displays address and location.
     *
     * @private
     * @function geoCode
     * @memberof Bolt.fields.geolocation
     *
     * @param {FieldGeolocation} field - Field data.
     * @param {string|undefined} address - Address to display.
     * @param {string|undefined} latitude - Latitude to display.
     * @param {string|undefined} longitude - Longitude to display.
     */
    function display(field, address, latitude, longitude) {
        field.matched.val(address || '');
        field.latitude.val(latitude || '');
        field.longitude.val(longitude || '');
    }

    /**
     * Displays address and location.
     *
     * @private
     * @function initGoogleMap
     * @memberof Bolt.fields.geolocation
     *
     * @param {FieldGeolocation} field - Field data.
     * @param {float} latitude - Initial latitude.
     * @param {float} longitude - Initial longitude.
     */
    function initGoogleMap(field, latitude, longitude) {
        var options = {
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: new google.maps.LatLng(latitude, longitude),
            //disableDoubleClickZoom: true,
            //addMarker: false,
            //
            // Controls
            // panControl: false,
            // zoomControl: false,
            // zoomControlOptions: {
            //     style: google.maps.ZoomControlStyle.DEFAULT  // SMALL/LARGE/DEFAULT
            //     position: google.maps.ControlPosition.LEFT_TOP
            // },
            // mapTypeControl: false,
            // mapTypeControlOptions: {
            //     style: google.maps.MapTypeControlStyle.DEFAULT  // HORIZONTAL_BAR/DROPDOWN_MENU/DEFAULT
            // },
            // scaleControl: false,
            //scaleControlOptions {
            //},
            streetViewControl: false
            // overviewMapControl: false,
            // overviewMapControlOptions: {
            // }
            // rotateControl: false,
            //
        };

        // Generate a new map and attach it to the mapholder.
        field.map = new google.maps.Map(field.mapholder[0], options);

        // Add marker
        field.marker = new google.maps.Marker({
            map: field.map,
            position: options.center,
            title: bolt.data('field.geolocation.marker'),
            draggable: true,
            animation: google.maps.Animation.DROP,
            icon: bolt.conf('paths.app') + 'view/img/pin_red.png'
        });

        // Set coordinates when marker pin was moved.
        google.maps.event.addListener(field.marker, 'mouseup', function () {
            geoCode(field, {latLng: field.marker.getPosition()});
        });

        // Update location when typed into address field.
        field.address.on('propertychange input', function () {
            clearTimeout(field.timeout);
            field.timeout = setTimeout(function () {
                var address = field.address.val();

                geoCode(field, address.length > 2 ? {address: address} : undefined);
            }, 800);
        });

        // Resize the map when it get's visible after tab change
        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            if (field.mapholder.closest('div.tab-pane').hasClass('active')) {
                google.maps.event.trigger(field.map, 'resize');
            }
        });
    }

    // Apply mixin container
    bolt.fields.geolocation = geolocation;

})(Bolt || {}, jQuery);
