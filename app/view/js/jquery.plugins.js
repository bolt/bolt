
/*
 Copyright (c) 2011 Sean Cusack

 MIT-LICENSE:

 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

(function($){

    var TEXTAREA_ID = 'jquery-catchpaste-textarea';
    function getTextArea() {
        var ta = $("#"+TEXTAREA_ID);
        if( ta.length <= 0 ) ta = $('<textarea id="'+TEXTAREA_ID+'">').css("left","-9999px").css("position","absolute").css("width","1px").css("height","1px").appendTo("body");
        return ta;
    }

    function waitForPasteData( callback, options, returnHereWhenDone ) {
        // the "this" here is the target of the paste command, so the <input>
        var textarea = getTextArea();
        textarea.css("display","block").val("");
        var target = $(this);
        function returnWhenDone(event) {
            // the "this" here is the target of the keyup event, so the <textarea>
            $(textarea).css("display","none");
            $(textarea).unbind("keyup");
            var pasted = $(textarea).val();
            var called = callback.call( $(target), pasted, options );
            //console.log(pasted,called);
            $(target).focus();
            if( called !== null )
            {
                $(target).val( $(target).caret().replace( called ) );
            }
        }
        textarea.focus();
        textarea.bind( "keyup", returnWhenDone );
    }

    // why? there's no logical way (that i can find) to trap text that's being dragged in, including newlines
    // --> by the time it hits the input, the newlines have been stripped, and before that point, there's no
    //     way to retrieve it from the events leading up to it, for things dragged in that were not explicitly
    //     set to draggable (and thus can be tracked nicely)A
    // why do i think this is impossible? because even googledocs spreadsheets don't support it. QED :-P
    function cancelDragDrop(event) {
        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    var IS_FIREFOX = null;

    $.fn.catchpaste = function( callback, options ) {
        //console.log(callback,options);
        if( ! options  ) options = {};
        if( IS_FIREFOX === null )
        {
            if(navigator.userAgent.match(/Firefox/)) {
                IS_FIREFOX = true;
            } else {
                IS_FIREFOX = false;
            }
        }
        if( IS_FIREFOX )
        {
            // works for Firefox:
            var pasteEvent   = "keydown";
            var pasteClosure = function(event) {
                // the "this" here is the target of the paste command, so the <input>
                if( ( ( event.keyCode == 86 ) || ( event.keyCode == 118 ) ) && ( event.metaKey || event.ctrlKey ) )
                { // CTRL-V or COMMAND-V
                    waitForPasteData.call( $(this), callback, options );
                }
            }
        }
        else
        {
            // works for Chrome and Safari:
            var pasteEvent   = "paste";
            var pasteClosure = function(event) {
                // the "this" here is the target of the paste command, so the <input>
                waitForPasteData.call( $(this), callback, options );
            }
        }
        return $(this).each( function() { $(this).bind( pasteEvent, pasteClosure ); $(this).bind("dragover",cancelDragDrop); } );
    };

})(jQuery);


/*
 Bootstrap - File Input
 ======================

 This is meant to convert all file input tags into a set of elements that displays consistently in all browsers.

 Converts all
 <input type="file">
 into Bootstrap buttons
 <a class="btn">Browse</a>

 */
$(function() {

    $.fn.bootstrapFileInput = function() {

        this.each(function(i,elem){

            var $elem = $(elem);

            // Maybe some fields don't need to be standardized.
            if (typeof $elem.attr('data-bfi-disabled') != 'undefined') {
                return;
            }

            // Set the word to be displayed on the button
            var buttonWord = 'Browse';

            if (typeof $elem.attr('title') != 'undefined') {
                buttonWord = $elem.attr('title');
            }

            // Start by getting the HTML of the input element.
            // Thanks for the tip http://stackoverflow.com/a/1299069
            var input = $('<div>').append( $elem.eq(0).clone() ).html();
            var className = '';

            if (!!$elem.attr('class')) {
                className = ' ' + $elem.attr('class');
            }

            // Now we're going to replace that input field with a Bootstrap button.
            // The input will actually still be there, it will just be float above and transparent (done with the CSS).
            $elem.replaceWith('<a class="file-input-wrapper btn' + className + '">'+buttonWord+input+'</a>');
        })

            // After we have found all of the file inputs let's apply a listener for tracking the mouse movement.
            // This is important because the in order to give the illusion that this is a button in FF we actually need to move the button from the file input under the cursor. Ugh.
            .promise().done( function(){

                // As the cursor moves over our new Bootstrap button we need to adjust the position of the invisible file input Browse button to be under the cursor.
                // This gives us the pointer cursor that FF denies us
                $('.file-input-wrapper').mousemove(function(cursor) {

                    var input, wrapper,
                        wrapperX, wrapperY,
                        inputWidth, inputHeight,
                        cursorX, cursorY;

                    // This wrapper element (the button surround this file input)
                    wrapper = $(this);
                    // The invisible file input element
                    input = wrapper.find("input");
                    // The left-most position of the wrapper
                    wrapperX = wrapper.offset().left;
                    // The top-most position of the wrapper
                    wrapperY = wrapper.offset().top;
                    // The with of the browsers input field
                    inputWidth= input.width();
                    // The height of the browsers input field
                    inputHeight= input.height();
                    //The position of the cursor in the wrapper
                    cursorX = cursor.pageX;
                    cursorY = cursor.pageY;

                    //The positions we are to move the invisible file input
                    // The 20 at the end is an arbitrary number of pixels that we can shift the input such that cursor is not pointing at the end of the Browse button but somewhere nearer the middle
                    moveInputX = cursorX - wrapperX - inputWidth + 20;
                    // Slides the invisible input Browse button to be positioned middle under the cursor
                    moveInputY = cursorY- wrapperY - (inputHeight/2);

                    // Apply the positioning styles to actually move the invisible file input
                    input.css({
                        left:moveInputX,
                        top:moveInputY
                    });
                });

                $('.file-input-wrapper input[type=file]').change(function(){

                    var fileName;
                    fileName = $(this).val();

                    // Remove any previous file names
                    $(this).parent().next('.file-input-name').remove();
                    if (!!$(this).prop('files') && $(this).prop('files').length > 1) {
                        fileName = $(this)[0].files.length+' files';
                        //$(this).parent().after('<span class="file-input-name">'+$(this)[0].files.length+' files</span>');
                    }
                    else {
                        // var fakepath = 'C:\\fakepath\\';
                        // fileName = $(this).val().replace('C:\\fakepath\\','');
                        fileName = fileName.substring(fileName.lastIndexOf('\\')+1,fileName.length);
                    }

                    $(this).parent().after('<span class="file-input-name">'+fileName+'</span>');
                });

            });

    };

// Add the styles before the first stylesheet
// This ensures they can be easily overridden with developer styles
    var cssHtml = '<style>'+
        '.file-input-wrapper { overflow: hidden; position: relative; cursor: pointer; z-index: 1; }'+
        '.file-input-wrapper input[type=file], .file-input-wrapper input[type=file]:focus, .file-input-wrapper input[type=file]:hover { position: absolute; top: 0; left: 0; cursor: pointer; opacity: 0; filter: alpha(opacity=0); z-index: 99; outline: 0; }'+
        '.file-input-name { margin-left: 8px; }'+
        '</style>';
    $('link[rel=stylesheet]').eq(0).before(cssHtml);

});


/*!
 * jquery.tagcloud.js
 * A Simple Tag Cloud Plugin for JQuery
 *
 * https://github.com/addywaddy/jquery.tagcloud.js
 * created by Adam Groves
 */
(function($) {

  /*global jQuery*/
  "use strict";

  var compareWeights = function(a, b)
  {
    return a - b;
  };

  // Converts hex to an RGB array
  var toRGB = function(code) {
    if (code.length === 4) {
      code = code.replace(/(\w)(\w)(\w)/gi, "\$1\$1\$2\$2\$3\$3");
    }
    var hex = /(\w{2})(\w{2})(\w{2})/.exec(code);
    return [parseInt(hex[1], 16), parseInt(hex[2], 16), parseInt(hex[3], 16)];
  };

  // Converts an RGB array to hex
  var toHex = function(ary) {
    return "#" + jQuery.map(ary, function(i) {
      var hex =  i.toString(16);
      hex = (hex.length === 1) ? "0" + hex : hex;
      return hex;
    }).join("");
  };

  var colorIncrement = function(color, range) {
    return jQuery.map(toRGB(color.end), function(n, i) {
      return (n - toRGB(color.start)[i])/range;
    });
  };

  var tagColor = function(color, increment, weighting) {
    var rgb = jQuery.map(toRGB(color.start), function(n, i) {
      var ref = Math.round(n + (increment[i] * weighting));
      if (ref > 255) {
        ref = 255;
      } else {
        if (ref < 0) {
          ref = 0;
        }
      }
      return ref;
    });
    return toHex(rgb);
  };

  $.fn.tagcloud = function(options) {

    var opts = $.extend({}, $.fn.tagcloud.defaults, options);
    var tagWeights = this.map(function(){
      return $(this).attr("rel");
    });
    tagWeights = jQuery.makeArray(tagWeights).sort(compareWeights);
    var lowest = tagWeights[0];
    var highest = tagWeights.pop();
    var range = highest - lowest;
    if(range === 0) {range = 1;}
    // Sizes
    var fontIncr, colorIncr;
    if (opts.size) {
      fontIncr = (opts.size.end - opts.size.start)/range;
    }
    // Colors
    if (opts.color) {
      colorIncr = colorIncrement (opts.color, range);
    }
    return this.each(function() {
      var weighting = $(this).attr("rel") - lowest;
      if (opts.size) {
        $(this).css({"font-size": opts.size.start + (weighting * fontIncr) + opts.size.unit});
      }
      if (opts.color) {
        $(this).css({"color": tagColor(opts.color, colorIncr, weighting)});
      }
    });
  };

  $.fn.tagcloud.defaults = {
    size: {start: 14, end: 18, unit: "pt"}
  };

})(jQuery);

/*
 * jQuery Hotkeys Plugin
 * Copyright 2010, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Based upon the plugin by Tzury Bar Yochay:
 * http://github.com/tzuryby/hotkeys
 *
 * Original idea by:
 * Binny V A, http://www.openjs.com/scripts/events/keyboard_shortcuts/
 */

(function(jQuery){

    jQuery.hotkeys = {
        version: "0.8+",

        specialKeys: {
            8: "backspace", 9: "tab", 13: "return", 16: "shift", 17: "ctrl", 18: "alt", 19: "pause",
            20: "capslock", 27: "esc", 32: "space", 33: "pageup", 34: "pagedown", 35: "end", 36: "home",
            37: "left", 38: "up", 39: "right", 40: "down", 45: "insert", 46: "del",
            96: "0", 97: "1", 98: "2", 99: "3", 100: "4", 101: "5", 102: "6", 103: "7",
            104: "8", 105: "9", 106: "*", 107: "+", 109: "-", 110: ".", 111 : "/",
            112: "f1", 113: "f2", 114: "f3", 115: "f4", 116: "f5", 117: "f6", 118: "f7", 119: "f8",
            120: "f9", 121: "f10", 122: "f11", 123: "f12", 144: "numlock", 145: "scroll", 188: ",", 190: ".",
            191: "/", 224: "meta"
        },

        shiftNums: {
            "`": "~", "1": "!", "2": "@", "3": "#", "4": "$", "5": "%", "6": "^", "7": "&",
            "8": "*", "9": "(", "0": ")", "-": "_", "=": "+", ";": ": ", "'": "\"", ",": "<",
            ".": ">",  "/": "?",  "\\": "|"
        }
    };

    function keyHandler( handleObj ) {

        var origHandler = handleObj.handler,
        //use namespace as keys so it works with event delegation as well
        //will also allow removing listeners of a specific key combination
        //and support data objects
            keys = (handleObj.namespace || "").toLowerCase().split(" ");
        keys = jQuery.map(keys, function(key) { return key.split("."); });

        //no need to modify handler if no keys specified
        if (keys.length === 1 && (keys[0] === "" || keys[0] === "autocomplete")) {
            return;
        }

        handleObj.handler = function( event ) {
            // Don't fire in text-accepting inputs that we didn't directly bind to
            // important to note that $.fn.prop is only available on jquery 1.6+
            if ( this !== event.target && (/textarea|select/i.test( event.target.nodeName ) ||
                event.target.type === "text" || $(event.target).prop('contenteditable') == 'true' )) {
                return;
            }

            // Keypress represents characters, not special keys
            var special = event.type !== "keypress" && jQuery.hotkeys.specialKeys[ event.which ],
                character = String.fromCharCode( event.which ).toLowerCase(),
                key, modif = "", possible = {};

            // check combinations (alt|ctrl|shift+anything)
            if ( event.altKey && special !== "alt" ) {
                modif += "alt_";
            }

            if ( event.ctrlKey && special !== "ctrl" ) {
                modif += "ctrl_";
            }

            // TODO: Need to make sure this works consistently across platforms
            if ( event.metaKey && !event.ctrlKey && special !== "meta" ) {
                modif += "meta_";
            }

            if ( event.shiftKey && special !== "shift" ) {
                modif += "shift_";
            }

            if ( special ) {
                possible[ modif + special ] = true;

            } else {
                possible[ modif + character ] = true;
                possible[ modif + jQuery.hotkeys.shiftNums[ character ] ] = true;

                // "$" can be triggered as "Shift+4" or "Shift+$" or just "$"
                if ( modif === "shift_" ) {
                    possible[ jQuery.hotkeys.shiftNums[ character ] ] = true;
                }
            }

            for ( var i = 0, l = keys.length; i < l; i++ ) {
                if ( possible[ keys[i] ] ) {
                    return origHandler.apply( this, arguments );
                }
            }
        };
    }

    jQuery.each([ "keydown", "keyup", "keypress" ], function() {
        jQuery.event.special[ this ] = { add: keyHandler };
    });

})( jQuery );



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
