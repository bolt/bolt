/**
 * DateTime object
 */
var DateTime = Backbone.Model.extend({

    defaults: {
        is24h: true
    },

    initialize: function () {
        // Set global datepicker locale
        $.datepicker.setDefaults($.datepicker.regional[bolt.locale.long]);
        // Remember if this locale uses 24h format
        this.set('is24h', moment.localeData()._longDateFormat.LT.replace(/\[.+?\]/gi, '').match(/A/) ? false : true);

        // Initialize each date/datetime input
        $('.datepicker').each(function(){
            var id = $(this).attr('id').replace(/-date$/, ''),
                inpDate = $(this),
                inpTime = $('#' + id + '-time'),
                inpData = $('#' + id),
                setDate = $.datepicker.parseDate('yy-mm-dd', inpData.val()),
                options = {},
                fieldOptions = $(this).data('field-options'),
                setfnc;

            // For debug purpose make hidden datafields visible
            if (1) {
                inpData.attr('type', 'text');
            }
            setfnc = function () {
                var date = moment(inpDate.datepicker('getDate')),
                    time = moment([2001, 11, 24]),
                    hours = 0,
                    minutes = 0,
                    h,
                    t;

                // Process time field
                if (inpTime.length) {
                    res = inpTime.val().match(/^\s*(?:(?:([01]?[0-9]|2[0-3])[:,.]([0-5]?[0-9]))|(1[012]|0?[1-9])[:,.]([0-5]?[0-9])(?:\s*([AP])[. ]?M\.?))\s*$/i);
                    if (res) {
                        hours = parseInt(res[1] ? res[1] :res[3]);
                        minutes = parseInt(res[2] ? res[2] :res[4]);
                        if ((res[5] === 'p' || res[5] === 'P') && hours !== 12) {
                            hours += 12;
                        } else if ((res[5] === 'a' || res[5] === 'A') && hours === 12) {
                            hours -= 12;
                        }
                        time = moment([2001, 11, 24, hours, minutes]);
                    }
                }
                // Set data
                if (date.isValid()) {
                    inpData.val(date.format('YYYY-MM-DD') + ' ' + time.format('HH:mm:00'));
                } else if (inpData.val() === '') {
                    inpData.val('');
                }
                // Write back
                if (inpData.val() !== '' && date.isValid()) {
                    inpDate.datepicker('setDate', $.datepicker.parseDate('yy-mm-dd', date.format('YYYY-MM-DD')));
                    if (inpTime.length) {
                        if (this.get('is24h')) {
                            t = inpData.val().slice(11, 16);
                        } else {
                            h = parseInt(inpData.val().slice(11, 13));
                            t = (inpData.val().slice(11, 13) % 12 || 12) + inpData.val().slice(13, 16) + ' ' + (h < 12 ? 'AM' : 'PM');
                        }
                        inpTime.val(t);
                    }
                } else {
                    inpDate.datepicker('setDate', '');
                    inpTime.val('');
                }
            };

            // Parse override settings from field in contenttypes.yml
            for (key in fieldOptions) {
                if (fieldOptions.hasOwnProperty(key)) {
                    options[key] = fieldOptions[key];
                }
            }

            // Update hidden field on selection
            options.onSelect = setfnc;

            // Set Datepicker
            inpDate.datepicker(options);
            inpDate.datepicker('setDate', setDate);

            // If a time field exists, bind it
            if (inpTime.length) {
                if (setDate == '' || inpData.val() === '') {
                    inpTime.val('');
                } else {
                    if (this.get('is24h')) {
                        t = inpData.val().slice(11, 16);
                    } else {
                        h = parseInt(inpData.val().slice(11, 13));
                        t = (inpData.val().slice(11, 13) % 12 || 12) + inpData.val().slice(13, 16) + ' ' + (h < 12 ? 'AM' : 'PM');
                    }
                    inpTime.val(t);
                }
                inpTime.change(setfnc);
            }
        });
    }
});
