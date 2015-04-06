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
     * @property {string} key - The field key
     * @property {string} lat: - Latitude.
     * @property {string} lon - Longitude.
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
        var latitude = parseFloat(fconf.lat),
            longitude = parseFloat(fconf.lon);

        // Default location is Two Kings, for now.
        if (latitude === 0 || isNaN(latitude)) {
            latitude = 52.08184;
        }
        if (longitude === 0 || isNaN(longitude)) {
            longitude = 4.292368;
        }

        $("#" + fconf.key + "-address").bind('propertychange input', function () {
            clearTimeout(geotimeout);
            geotimeout = setTimeout(function () {
                bindGeoAjax(fconf.key);
            }, 800);
        });

        $("#map-" + fconf.key).goMap({
            latitude: latitude,
            longitude: longitude,
            zoom: 15,
            maptype: 'ROADMAP',
            disableDoubleClickZoom: true,
            addMarker: false,
            icon: Bolt.conf('paths.app') + 'view/img/pin_red.png',
            markers: [{
                latitude: latitude,
                longitude: longitude,
                id: 'pinmarker',
                title: 'Pin',
                draggable: true
            }]
        });

        $.goMap.createListener(
            {
                type: 'marker',
                marker: 'pinmarker'
            },
            'mouseup',
            function () {
                updateGeoCoords(fconf.key);
            }
        );

        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            if ($("#map-" + fconf.key).closest('div.tab-pane').hasClass('active')) {
                $("#map-" + fconf.key).goMap();
                google.maps.event.trigger($.goMap.map, 'resize');
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
     * @param {string} key - Field key.
     */
    function bindGeoAjax(key) {
        var address = $("#" + key + "-address").val();

        // If address is emptied, clear the address fields.
        if (address.length < 2) {
            $('#' + key + '-latitude').val('');
            $('#' + key + '-longitude').val('');
            $('#' + key + '-reversegeo').html('');
            $('#' + key + '-formatted_address').val('');
            return;
        }

        $.goMap.setMap({address: address});
        $.goMap.setMarker('pinmarker', {address: address});

        setTimeout(function () {
            updateGeoCoords(key);
        }, 500);
    }

    /**
     * updateGeoCoords.
     *
     * @private
     * @function updateGeoCoords
     * @memberof Bolt.fields.geolocation
     *
     * @param {string} key - Field key.
     */
    function updateGeoCoords(key) {
        var markers = $.goMap.getMarkers(),
            marker,
            geocoder,
            latlng;

        if (typeof markers[0] !== "undefined") {
            marker = markers[0].split(",");

            if (typeof marker[0] !== "undefined" && typeof marker[1] !== "undefined") {
                $('#' + key + '-latitude').val(marker[0]);
                $('#' + key + '-longitude').val(marker[1]);

                // update the 'according to Google' info:
                geocoder = new google.maps.Geocoder();
                latlng = new google.maps.LatLng(marker[0], marker[1]);

                geocoder.geocode({latLng: latlng}, function (results, status) {
                    $('#' + key + '-reversegeo').html(results[0].formatted_address);
                    $('#' + key + '-formatted_address').val(results[0].formatted_address);
                });
            }
        }
    }

    // Apply mixin container
    bolt.fields.geolocation = geolocation;

})(Bolt || {}, jQuery);
