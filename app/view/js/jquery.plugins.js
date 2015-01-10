// Small jQuery plugin to detect whether or not a form's values have been changed.
// @see: https://gist.github.com/DrPheltRight/4131266
// Written by Luke Morton, licensed under MIT. Adapted for Bolt by Bob.
(function ($) {

    $.fn.watchChanges = function () {

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        for(var instanceName in CKEDITOR.instances) {
            CKEDITOR.instances[instanceName].updateElement();
        }

        return this.each(function () {
            $.data(this, 'formHash', $(this).serialize());
        });
    };

    $.fn.hasChanged = function () {
        var hasChanged = false;

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        for(var instanceName in CKEDITOR.instances) {
            CKEDITOR.instances[instanceName].updateElement();
        }

        this.each(function () {
            var formHash = $.data(this, 'formHash');

            if (formHash != null && formHash !== $(this).serialize()) {
                hasChanged = true;
                return false;
            }
        });

        return hasChanged;
    };

}).call(this, jQuery);

/*!
 * jQuery Cookie Plugin v1.4.0
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function encode(s) {
        return config.raw ? s : encodeURIComponent(s);
    }

    function decode(s) {
        return config.raw ? s : decodeURIComponent(s);
    }

    function stringifyCookieValue(value) {
        return encode(config.json ? JSON.stringify(value) : String(value));
    }

    function parseCookieValue(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape...
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }

        try {
            // Replace server-side written pluses with spaces.
            // If we can't decode the cookie, ignore it, it's unusable.
            // If we can't parse the cookie, ignore it, it's unusable.
            s = decodeURIComponent(s.replace(pluses, ' '));
            return config.json ? JSON.parse(s) : s;
        } catch(e) {}
    }

    function read(s, converter) {
        var value = config.raw ? s : parseCookieValue(s);
        return $.isFunction(converter) ? converter(value) : value;
    }

    var config = $.cookie = function (key, value, options) {

        // Write

        if (value !== undefined && !$.isFunction(value)) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setTime(+t + days * 864e+5);
            }

            return (document.cookie = [
                encode(key), '=', stringifyCookieValue(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // Read

        var result = key ? undefined : {};

        // To prevent the for loop in the first place assign an empty array
        // in case there are no cookies at all. Also prevents odd result when
        // calling $.cookie().
        var cookies = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = parts.join('=');

            if (key && key === name) {
                // If second argument (value) is a function it's a converter...
                result = read(cookie, value);
                break;
            }

            // Prevent storing a cookie that we couldn't decode.
            if (!key && (cookie = read(cookie)) !== undefined) {
                result[name] = cookie;
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        if ($.cookie(key) === undefined) {
            return false;
        }

        // Must not alter options, thus extending a fresh object...
        $.cookie(key, '', $.extend({}, options, { expires: -1 }));
        return !$.cookie(key);
    };

}));


/*
 * jQuery Format Date/Time - v1.1.4 - 2014-08-25
 * https://github.com/agschwender/jquery.formatDateTime
 * Copyright (c) 2014 Adam Gschwender
 * Licensed MIT, GPLv2
 */
;(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS style for Browserify
        module.exports = factory;
    } else {
        // Browser globals: jQuery or jQuery-like library, such as Zepto
        factory(window.jQuery || window.$);
    }
}(function($) {

    var ticksTo1970 = (((1970 - 1) * 365 + Math.floor(1970 / 4)
                        - Math.floor(1970 / 100)
                        + Math.floor(1970 / 400)) * 24 * 60 * 60 * 10000000);

    var formatDateTime = function(format, date, settings) {
        var output = '';
        var literal = false;
        var iFormat = 0;

        // Check whether a format character is doubled
        var lookAhead = function(match) {
            var matches = (iFormat + 1 < format.length
                           && format.charAt(iFormat + 1) == match);
            if (matches) {
                iFormat++;
            }
            return matches;
        };

        // Format a number, with leading zero if necessary
        var formatNumber = function(match, value, len) {
            var num = '' + value;
            if (lookAhead(match)) {
                while (num.length < len) {
                    num = '0' + num;
                }
            }
            return num;
        };

        // Format a name, short or long as requested
        var formatName = function(match, value, shortNames, longNames) {
            return (lookAhead(match) ? longNames[value] : shortNames[value]);
        };

        // Get the value for the supplied unit, e.g. year for y
        var getUnitValue = function(unit) {
            switch (unit) {
            case 'y': return date.getFullYear();
            case 'm': return date.getMonth() + 1;
            case 'd': return date.getDate();
            case 'g': return date.getHours() % 12 || 12;
            case 'h': return date.getHours();
            case 'i': return date.getMinutes();
            case 's': return date.getSeconds();
            case 'u': return date.getMilliseconds();
            default: return '';
            }
        };

        for (iFormat = 0; iFormat < format.length; iFormat++) {
            if (literal) {
                if (format.charAt(iFormat) == "'" && !lookAhead("'")) {
                    literal = false;
                }
                else {
                    output += format.charAt(iFormat);
                }
            } else {
                switch (format.charAt(iFormat)) {
                case 'a':
                    output += date.getHours() < 12
                        ? settings.ampmNames[0]
                        : settings.ampmNames[1];
                    break;
                case 'd':
                    output += formatNumber('d', date.getDate(), 2);
                    break;
                case 'S':
                    var v = getUnitValue(iFormat && format.charAt(iFormat-1));
                    output += (v && (settings.getSuffix || $.noop)(v)) || '';
                    break;
                case 'D':
                    output += formatName('D',
                                         date.getDay(),
                                         settings.dayNamesShort,
                                         settings.dayNames);
                    break;
                case 'o':
                    var end = new Date(date.getFullYear(),
                                       date.getMonth(),
                                       date.getDate()).getTime();
                    var start = new Date(date.getFullYear(), 0, 0).getTime();
                    output += formatNumber(
                        'o', Math.round((end - start) / 86400000), 3);
                    break;
                case 'g':
                    output += formatNumber('g', date.getHours() % 12 || 12, 2);
                    break;
                case 'h':
                    output += formatNumber('h', date.getHours(), 2);
                    break;
                case 'u':
                    output += formatNumber('u', date.getMilliseconds(), 3);
                    break;
                case 'i':
                    output += formatNumber('i', date.getMinutes(), 2);
                    break;
                case 'm':
                    output += formatNumber('m', date.getMonth() + 1, 2);
                    break;
                case 'M':
                    output += formatName('M',
                                         date.getMonth(),
                                         settings.monthNamesShort,
                                         settings.monthNames);
                    break;
                case 's':
                    output += formatNumber('s', date.getSeconds(), 2);
                    break;
                case 'y':
                    output += (lookAhead('y')
                               ? date.getFullYear()
                               : (date.getYear() % 100 < 10 ? '0' : '')
                               + date.getYear() % 100);
                    break;
                case '@':
                    output += date.getTime();
                    break;
                case '!':
                    output += date.getTime() * 10000 + ticksTo1970;
                    break;
                case "'":
                    if (lookAhead("'")) {
                        output += "'";
                    } else {
                        literal = true;
                    }
                    break;
                default:
                    output += format.charAt(iFormat);
                }
            }
        }
        return output;
    };

    $.fn.formatDateTime = function(format, settings) {
        settings = $.extend({}, $.formatDateTime.defaults, settings);

        this.each(function() {
            var date = $(this).attr(settings.attribute);

            // Use explicit format string first,
            // then fallback to format attribute
            var fmt = format || $(this).attr(settings.formatAttribute);

            if (typeof date === 'undefined' || date === false) {
                date = $(this).text();
            }

            if (date === '') {
                $(this).text('');
            } else {
                $(this).text(formatDateTime(fmt, new Date(date), settings));
            }
        });

        return this;
    };

    /**
       Format a date object into a string value.
       The format can be combinations of the following:
       a - Ante meridiem and post meridiem
       d  - day of month (no leading zero)
       dd - day of month (two digit)
       o  - day of year (no leading zeros)
       oo - day of year (three digit)
       D  - day name short
       DD - day name long
       g  - 12-hour hour format of day (no leading zero)
       gg - 12-hour hour format of day (two digit)
       h  - 24-hour hour format of day (no leading zero)
       hh - 24-hour hour format of day (two digit)
       u  - millisecond of second (no leading zeros)
       uu - millisecond of second (three digit)
       i  - minute of hour (no leading zero)
       ii - minute of hour (two digit)
       m  - month of year (no leading zero)
       mm - month of year (two digit)
       M  - month name short
       MM - month name long
       S  - ordinal suffix for the previous unit
       s  - second of minute (no leading zero)
       ss - second of minute (two digit)
       y  - year (two digit)
       yy - year (four digit)
       @  - Unix timestamp (ms since 01/01/1970)
       !  - Windows ticks (100ns since 01/01/0001)
       '...' - literal text
       '' - single quote

       @param  format    string - the desired format of the date
       @param  date      Date - the date value to format
       @param  settings  Object - attributes include:
           ampmNames        string[2] - am/pm (optional)
           dayNamesShort    string[7] - abbreviated names of the days
                                        from Sunday (optional)
           dayNames         string[7] - names of the days from Sunday (optional)
           monthNamesShort  string[12] - abbreviated names of the months
                                         (optional)
           monthNames       string[12] - names of the months (optional)
           getSuffix        function(num) - accepts a number and returns
                                            its suffix
           attribute        string - Attribute which stores datetime, defaults
                                     to data-datetime, only valid when called
                                     on dom element(s). If not present,
                                     uses text.
           formatAttribute  string - Attribute which stores the format, defaults
                                     to data-dateformat.
       @return  string - the date in the above format
    */
    $.formatDateTime = function(format, date, settings) {
        settings = $.extend({}, $.formatDateTime.defaults, settings);
        if (!date) { return ''; }
        return formatDateTime(format, date, settings);
    };

    $.formatDateTime.defaults = {
        monthNames: ['January','February','March','April','May','June',
                     'July','August','September','October','November',
                     'December'],
        monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul',
                          'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday',
                   'Friday', 'Saturday'],
        dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ampmNames: ['AM', 'PM'],
        getSuffix: function (num) {
            if (num > 3 && num < 21) {
                return 'th';
            }

            switch (num % 10) {
            case 1:  return "st";
            case 2:  return "nd";
            case 3:  return "rd";
            default: return "th";
            }
        },
        attribute: 'data-datetime',
        formatAttribute: 'data-dateformat'
    };

}));