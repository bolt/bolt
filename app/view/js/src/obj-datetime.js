/**
 * DateTime/Date input combo initalization and handling
 */
bolt.datetimes = function () {
    /**
     * @typedef InputElements
     * @type {Object} data - Element holding the data
     * @type {Object} date - Date input element
     * @type {Object} time - Time input element
     * @type {Object} show - Show datepicker button
     * @type {Object} clear - Clear datepicker button
     * @type {boolean} hasTime - 'true' if it is a datetime input, else false
     */

     /**
     * Indicates if 24h or 12h time format should be used
     *
     * @type {boolean}
     * @private
     */
    var is24h;

     /**
     * Hold info on used DateTime/Date input combos
     *
     * @type {Array}
     * @private
     */
    var fields = [];

    /**
     * Evaluate the value(s) from the input field(s) and writes it to the data field
     *
     * @param {InputElements} field
     * @private
     */
    function evaluate(field) {
        var date = moment(field.date.datepicker('getDate')),
            time = moment([2001, 11, 24]),
            hours = 0,
            minutes = 0;

        // Process time field
        if (field.hasTime) {
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
            field.data.val(date.format('YYYY-MM-DD') + (field.hasTime ? ' ' + time.format('HH:mm:00') : ''));
        } else if (field.date.val() === '') {
            field.data.val('');
        } else {
            // Error
        }
    }

    /**
     * Displays the value read from the data field inside combos input field(s)
     *
     * @param {InputElements} field
     * @private
     */
    function display(field) {
        var date = '',
            time = '',
            hour,
            match;

        // Correct no depublish date
        if (field.data.attr('id') === 'datedepublish' && field.data.val() === '1900-01-01 00:00:00') {
            field.data.val('');
        }

        // If data field has a valid datetime or date
        match = field.data.val().match(/^(\d{4}-\d{2}-\d{2})(?: (\d{2}:\d{2}:\d{2}))?$/);
        if (match) {
            date = match[1];
            time = match[2] || '';
        }

        // Set date field
        field.date.datepicker('setDate', (date === '' || date === '0000-00-00') ? '' : $.datepicker.parseDate('yy-mm-dd', date));

        // Set time field
        if (field.hasTime) {
            if (time === '') {
                time = '';
            } else if (is24h) {
                time = field.data.val().slice(11, 16);
            } else {
                hour = parseInt(time.slice(0, 2));
                time = (hour % 12 || 12) + time.slice(2, 5) + (hour < 12 ? ' AM' : ' PM');
            }
            field.time.val(time);
        }
    }

    /**
     * Binds the datepicker to the date input and initializes it
     *
     * @param {InputElements} field
     * @private
     */
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
            // Set the date to "today", if nothing has been picked yet.
            if (!field.date.datepicker('getDate')) {
                field.date.datepicker('setDate', "+0");
            }
            field.date.datepicker('show');
        });
        // Bind clear button
        field.clear.click(function () {
            field.data.val('');
            display(field);
        });
    }

    /**
     * Collects all inputs belonging to a DateTime/Date input combo
     *
     * @param {Object} item - Data element
     * @returns {InputElements}
     */
    function elements(item) {
        var field = {},
            container = item.next();

        field.data = item;
        field.date = container.find('input.datepicker');
        field.time = container.find('input.timepicker');
        field.show = container.find('button.btn-tertiary');
        field.clear = container.find('button.btn-default');
        field.hasTime = (field.time.length > 0);

        return field;
    }

    return {
        /**
         * Initialize the datetime and date input combos
         */
        init: function () {
            // Set global datepicker locale
            $.datepicker.setDefaults($.datepicker.regional[bolt.locale.long]);

            // Find out if locale uses 24h format
            is24h = moment.localeData()._longDateFormat.LT.replace(/\[.+?\]/gi, '').match(/A/) ? false : true;

            // Initialize each available date/datetime field
            $('input.datetime').each(function () {
                var field = elements($(this));

                // Remember field data
                fields.push(field);

                // Uncomment for debug purpose to make hidden datafields visible
                // field.data.attr('type', 'text');

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
        },

        /**
         * Updates display of datetime and date inputs from their data fields
         */
        update: function () {
            for (var i in fields) {
                display(fields[i]);
            }
        }
    };
} ();
