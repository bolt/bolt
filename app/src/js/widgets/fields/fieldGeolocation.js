/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Geolocation field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldGeolocation
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldGeolocation', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldGeolocation.prototype */ {
        /**
         * Default options.
         *
         * @property {string} latitude  - Latitude
         * @property {string} longitude - Longitude
         */
        options: {
            latitude: '',
            longitude: ''
        },

        /**
         * The constructor of the geolocation field widget.
         *
         * @private
         */
        _create: function () {
            var self = this,
                fieldset = self.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} address   - Input: Address lookup
             * @property {Object} matched   - Readonly input: displaying matched address
             * @property {Object} mapholder - Element containing map
             * @property {Object} spinner   - Spinner element
             * @property {Object} latitude  - Input: Latitude
             * @property {Object} longitude - Input: Longitude
             * @property {Object} snap      - Checkbox: Snap
             */
            this._ui = {
                address:   fieldset.find('.address'),
                matched:   fieldset.find('.matched'),
                mapholder: fieldset.find('.mapholder'),
                spinner:   fieldset.find('.mapholder i'),
                latitude:  fieldset.find('.latitude'),
                longitude: fieldset.find('.longitude'),
                snap:      fieldset.find('.snap')
            };

            /**
             * Google map object.
             *
             * @type {Object|null}
             * @name _map
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._map = null;

            /**
             * Map marker.
             *
             * @type {Object|null}
             * @name _marker
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._marker = null;

            /**
             * Timeout resource for location resolving.
             *
             * @type {number}
             * @name _timeout
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @fires "Bolt.GoogleMapsAPI.Load.Request"
             * @listens "Bolt.GoogleMapsAPI.Load.Done"
             * @listens "Bolt.GoogleMapsAPI.Load.Fail"
             */
            this._timeout = 0;

            // Bind events.
            self._on({
                'click.expand': self._onExpand,
                'click.compress': self._onExpand
            });

            var onGmLoad = function () {
                bolt.events.off('Bolt.GoogleMapsAPI.Load.Done', onGmLoad);
                self._initGoogleMap(self.options.latitude, self.options.longitude);
            };
            bolt.events.on('Bolt.GoogleMapsAPI.Load.Done', onGmLoad);
            bolt.events.on('Bolt.GoogleMapsAPI.Load.Fail', function () {
                self._ui.spinner.removeClass('fa-spinner fa-spin').addClass('fa-refresh').one('click', function () {
                    self._ui.spinner.removeClass('fa-refresh').addClass('fa-spinner fa-spin');
                    bolt.events.fire('Bolt.GoogleMapsAPI.Load.Request');
                });
            });

            // Request loading of Google Maps API.
            bolt.events.fire('Bolt.GoogleMapsAPI.Load.Request');
        },

        /**
         * Geocode address or location and display result.
         *
         * @private
         * @param {Object|undefined} search - Optional address or location search
         */
        _geoCode: function (search) {
            var self = this;

            if (search) {
                (new google.maps.Geocoder()).geocode(
                    search,
                    function (results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            var location;

                            if (search.latLng && !self._ui.snap.is(':checked')) {
                                location = search.latLng;
                            } else {
                                location = results[0].geometry.location;
                            }

                            self._display(results[0].formatted_address, location.lat(), location.lng());

                            self._marker.setPosition(location);
                            self._map.setCenter(location);
                        }
                    }
                );
            }
            self._display();
        },

        /**
         * Displays address and location.
         *
         * @private
         * @param {string|undefined} address   - Address to display
         * @param {string|undefined} latitude  - Latitude to display
         * @param {string|undefined} longitude - Longitude to display
         */
        _display: function (address, latitude, longitude) {
            this._ui.matched.val(address || '');
            this._ui.latitude.val(latitude || '');
            this._ui.longitude.val(longitude || '');
        },

        /**
         * Displays address and location.
         *
         * @private
         * @param {float} latitude  - Initial latitude
         * @param {float} longitude - Initial longitude
         */
        _initGoogleMap: function (latitude, longitude) {
            var self = this;
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
            self._map = new google.maps.Map(self._ui.mapholder[0], options);

            // Add marker
            self._marker = new google.maps.Marker({
                map: self._map,
                position: options.center,
                title: bolt.data('field.geolocation.marker'),
                draggable: true,
                animation: google.maps.Animation.DROP,
                icon: bolt.conf('paths.app') + 'view/img/pin_red.png'
            });

            // Set coordinates when marker pin was moved.
            google.maps.event.addListener(self._marker, 'mouseup', function () {
                self._geoCode({latLng: self._marker.getPosition()});
            });

            // Update location when typed into address field.
            self._ui.address.on('propertychange input', function () {
                clearTimeout(self._timeout);
                self._timeout = setTimeout(function () {
                    var address = self._ui.address.val();

                    self._geoCode(address.length > 2 ? {address: address} : undefined);
                }, 800);
            });

            // Resize the map when it get's visible after tab change
            $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                if (self._ui.mapholder.closest('div.tab-pane').hasClass('active')) {
                    google.maps.event.trigger(self._map, 'resize');
                }
            });
        },

        /**
         * Expand/Compact button clicked.
         *
         * @private
         */
        _onExpand: function () {
            var markerPos = this._marker.getPosition();

            this._ui.mapholder.parent().toggleClass('expanded');
            google.maps.event.trigger(this._map, 'resize');
            this._map.setCenter(markerPos);
        }
    });
})(jQuery, Bolt);
