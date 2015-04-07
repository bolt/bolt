/**
 * Handling of geolocation input fields.
 *
 * @mixin
 * @namespace Bolt.fields.geolocation
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} google - Google.
 */
(function (bolt, $, google) {
    /**
     * Field configuration.
     *
     * @typedef {Object} FieldConf
     * @memberof Bolt.fields.slug
     *
     * @property {string} key - The field key
     * @property {string} latitude: - Latitude.
     * @property {string} longitude - Longitude.
     */

    /**
     * Field data.
     *
     * @typedef {Object} FieldData
     * @memberof Bolt.fields.slug
     *
     * @property {Object} address - Input: Address lookup.
     * @property {Object} formatted - Readonly input: displaying matched address
     * @property {Object} mapholder - Element holding the map.
     * @property {Object} latitude - Input: Latitude.
     * @property {Object} longitude - Input: Longitude.
     * @property {function} map - Input: Longitude.
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
                formatted: $(fieldset).find('.formatted'),
                mapholder: $(fieldset).find('.mapholder'),
                latitude: $(fieldset).find('.latitude'),
                longitude: $(fieldset).find('.longitude'),
                map: function () {
                    $(fieldset).find('.mapholder').goMap();
                    return $.goMap;
                }
            };

        field.address.bind('propertychange input', function () {
            clearTimeout(geotimeout);
            geotimeout = setTimeout(function () {
                bindGeoAjax(field);
            }, 800);
        });

        field.mapholder.goMap({
            latitude: fconf.latitude,
            longitude: fconf.longitude,
            zoom: 15,
            maptype: 'ROADMAP',
            disableDoubleClickZoom: true,
            addMarker: false,
            icon: bolt.conf('paths.app') + 'view/img/pin_red.png',
            markers: [{
                latitude: fconf.latitude,
                longitude: fconf.longitude,
                id: 'pinmarker',
                title: 'Pin',
                draggable: true
            }]
        });

        field.map().createListener(
            {
                type: 'marker',
                marker: 'pinmarker'
            },
            'mouseup',
            function () {
                updateGeoCoords(field);
            }
        );

        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            if (field.mapholder.closest('div.tab-pane').hasClass('active')) {
                google.maps.event.trigger(field.map().map, 'resize');
            }
        });
    };

    /**
     * Timeout.
     *
     * @private
     * @type {Array}
     * @memberof Bolt.fields.geolocation
     */
    var geotimeout;

    /**
     * bindGeoAjax.
     *
     * @private
     * @function bindGeoAjax
     * @memberof Bolt.fields.geolocation
     *
     * @param {FieldData} field - Field data.
     */
    function bindGeoAjax(field) {
        var address = field.address.val(),
            map;

        // If address is emptied, clear the address fields.
        if (address.length < 2) {
            field.latitude.val('');
            field.longitude.val('');
            field.formatted.val('');
        } else {
            map = field.map();
            map.setMap({address: address});
            map.setMarker('pinmarker', {address: address});

            setTimeout(
                function () {
                    updateGeoCoords(field);
                },
                500
            );
        }
    }

    /**
     * updateGeoCoords.
     *
     * @private
     * @function updateGeoCoords
     * @memberof Bolt.fields.geolocation
     *
     * @param {FieldData} field - Field data.
     */
    function updateGeoCoords(field) {
        var markers = field.map().getMarkers(),
            marker,
            geocoder;

        if (typeof markers[0] !== 'undefined') {
            marker = markers[0].split(',');

            if (typeof marker[0] !== 'undefined' && typeof marker[1] !== 'undefined') {
                field.latitude.val(marker[0]);
                field.longitude.val(marker[1]);

                // Update the 'according to Google' info:
                geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    {
                        latLng: new google.maps.LatLng(marker[0], marker[1])
                    },
                    function (results, status) {
                        field.formatted.val(results[0].formatted_address);
                    }
                );
            }
        }
    }

    // Apply mixin container
    bolt.fields.geolocation = geolocation;

})(Bolt || {}, jQuery, google);
