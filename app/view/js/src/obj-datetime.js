/**
 * DateTime object
 */
bolt.datetimes = function () {
    function evaluate(field) {
        var date = moment(field.date.datepicker('getDate')),
            time = moment([2001, 11, 24]),
            hours = 0,
            minutes = 0;

        // Process time field
        if (field.time.length) {
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

        // Set data field
        if (date.isValid()) {
            field.data.val(date.format('YYYY-MM-DD') + ' ' + time.format('HH:mm:00'));
        } else if (field.date.val() === '') {
            field.data.val('');
        } else {
            // Error
        }
    }

    function display(field) {
        var date = '',
            time = '',
            hour,
            match;

        // Correct no depublish date
        if (field.data.attr('id') === 'datedepublish' && field.data.val() === '1900-01-01 00:00:00') {
            field.data.val('');
        }

        // If data is a valid datetime
        match = field.data.val().match(/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:00)$/);
        if (match) {
            date = match[1];
            time = match[2];
        }

        // Set date field
        field.date.datepicker('setDate', (date === '') ? '' : $.datepicker.parseDate('yy-mm-dd', date));

        // Set time field
        if (field.time.length) {
            if (time === '') {
                time = '';
            } else if (field.is24h) {
                time = field.data.val().slice(11, 16);
            } else {
                hour = parseInt(time.slice(0, 2));
                time = (hour % 12 || 12) + time.slice(2, 5) + (hour < 12 ? ' AM' : ' PM');
            }
            field.time.val(time);
        }
    }

    function bindDatepicker(field) {
        var fieldOptions = field.date.data('field-options'),
            options = {
                showOn: 'none'
            };

        for (key in fieldOptions) {
            if (fieldOptions.hasOwnProperty(key)) {
                options[key] = fieldOptions[key];
            }
        }
        // Bind datepicker button
        field.date.datepicker(options);
        // Bind show button
        field.show.click(function () {
            console.log('CLICK:' +field.date.attr('id'));
            field.date.datepicker('show');
        });
        // Bind clear button
        field.clear.click(function () {
            field.data.val('');
            display(field);
        });
    }

    return {
        init: function () {
            // Set global datepicker locale
            $.datepicker.setDefaults($.datepicker.regional[bolt.locale.long]);

            // Find out if locale uses 24h format
            var is24h = moment.localeData()._longDateFormat.LT.replace(/\[.+?\]/gi, '').match(/A/) ? false : true;

            // Initialize each available date/datetime field
            $('.datepicker').each(function () {
                var setDate,
                    id = $(this).attr('id').replace(/-date$/, ''),
                    field = {
                        data: $('#' + id),
                        date: $(this),
                        time: $('#' + id + '-time'),
                        show: $('#' + id + '-show'),
                        clear: $('#' + id + '-clear'),
                        is24h: is24h
                    };

                // For debug purpose make hidden datafields visible
                if (true) {
                    field.data.attr('type', 'text');
                }

                // Bind datepicker to date field and set options from field in contenttypes.yml
                bindDatepicker(field);

                display(field);

                // Bind change action to date and time field
                field.date.change(function () {
                    evaluate(field);
                    display(field);
                });
                field.time.change(function () {
                    evaluate(field);
                    display(field);
                });
            });
        }
    };
}();
