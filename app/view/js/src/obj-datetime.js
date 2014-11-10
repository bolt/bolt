/**
 * DateTime object
 */
var datetimes = function () {
    var is24h;

    function hasChanged(field) {
        console.log('dattim:hasChanged()');

        var date = moment(field.date.datepicker('getDate')),
            time = moment([2001, 11, 24]),
            hours = 0,
            minutes = 0,
            h,
            t;

        // Process time field
        if (field.time) {
            res = field.time.val().match(/^\s*(?:(?:([01]?[0-9]|2[0-3])[:,.]([0-5]?[0-9]))|(1[012]|0?[1-9])[:,.]([0-5]?[0-9])(?:\s*([AP])[. ]?M\.?))\s*$/i);
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
            field.data.val(date.format('YYYY-MM-DD') + ' ' + time.format('HH:mm:00'));
        } else if (field.data.val() === '') {
            field.data.val('');
        }
        // Write back
        if (field.data.val() !== '' && date.isValid()) {
            field.date.datepicker('setDate', $.datepicker.parseDate('yy-mm-dd', date.format('YYYY-MM-DD')));
            if (field.time) {
                if (this.is24h) {
                    t = field.data.val().slice(11, 16);
                } else {
                    h = parseInt(field.data.val().slice(11, 13));
                    t = (field.data.val().slice(11, 13) % 12 || 12) + field.data.val().slice(13, 16) + ' ' + (h < 12 ? 'AM' : 'PM');
                }
                field.time.val(t);
            }
        } else {
            field.date.datepicker('setDate', '');
            field.time.val('');
        }
    }

    return {
        init: function () {
            // Set global datepicker locale
            $.datepicker.setDefaults($.datepicker.regional[bolt.locale.long]);
            // Find out if locale uses 24h format
            this.is24h = moment.localeData()._longDateFormat.LT.replace(/\[.+?\]/gi, '').match(/A/) ? false : true;

            // Initialize each available date/datetime input
            $('.datepicker').each(function () {
                var options = {},
                    setDate,
                    id = $(this).attr('id').replace(/-date$/, ''),
                    fieldOptions = $(this).data('field-options');
                    field = {
                        data: $('#' + id),
                        date: $(this),
                        time: $('#' + id + '-time')
                    };

                field.time = field.time.length ? field.time : false;
                setDate = $.datepicker.parseDate('yy-mm-dd', field.data.val());

                // For debug purpose make hidden datafields visible
                if (true) {
                    field.data.attr('type', 'text');
                }

                // Parse override settings from field in contenttypes.yml
                for (key in fieldOptions) {
                    if (fieldOptions.hasOwnProperty(key)) {
                        options[key] = fieldOptions[key];
                    }
                }

                // Update hidden field on selection
                options.onSelect = function () {
                    hasChanged(field);
                };

                // Set Datepicker
                field.date.datepicker(options);
                field.date.datepicker('setDate', setDate);

                // If a time field exists, bind it
                if (field.time) {
                    if (setDate == '' || field.data.val() === '') {
                        field.time.val('');
                    } else {
                        if (this.is24h) {
                            t = field.data.val().slice(11, 16);
                        } else {
                            h = parseInt(field.data.val().slice(11, 13));
                            t = (field.data.val().slice(11, 13) % 12 || 12) + field.data.val().slice(13, 16) + ' ' + (h < 12 ? 'AM' : 'PM');
                        }
                        field.time.val(t);
                    }
                    field.time.change(function () {
                        hasChanged(field);
                    });
                }
            });
        }
    };
}();
