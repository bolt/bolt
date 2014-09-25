var geotimeout;

function updateGeoCoords(key) {
    var markers = $.goMap.getMarkers(),
        marker = markers[0].split(","),
        geocoder,
        latlng;

    if (typeof(marker[0] !== "undefined")) {
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

function bindGeolocation(key, latitude, longitude) {
    latitude = parseFloat(latitude);
    longitude = parseFloat(longitude);

    // Default location is Two Kings, for now.
    if (latitude === 0 || isNaN(latitude)) {
        latitude = 52.08184;
    }
    if (longitude === 0 || isNaN(longitude)) {
        longitude = 4.292368;
    }

    $("#" + key + "-address").bind('propertychange input', function () {
        clearTimeout(geotimeout);
        geotimeout = setTimeout(function () {
            bindGeoAjax(key);
        }, 800);
    });

    $("#map-" + key).goMap({
        latitude: latitude,
        longitude: longitude,
        zoom: 15,
        maptype: 'ROADMAP',
        disableDoubleClickZoom: true,
        addMarker: false,
        icon: bolt.paths.app + 'view/img/pin_red.png',
        markers: [{
            latitude: latitude,
            longitude: longitude,
            id: 'pinmarker',
            title: 'Pin',
            draggable: true
        }]
    });

    // Handler for when the marker is dropped.
    $.goMap.createListener(
        {type: 'marker', marker: 'pinmarker'},
        'mouseup',
        function () {
            updateGeoCoords(key);
        }
    );
}
