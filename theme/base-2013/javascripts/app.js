;(function ($, window, undefined) {
    'use strict';

    var $doc = $(document),
      Modernizr = window.Modernizr;

    $(document).ready(function() {
        $.fn.foundationTopBar           ? $doc.foundationTopBar() : null;
    });

    // Hide address bar on mobile devices (except if #hash present, so we don't mess up deep linking).
    if (Modernizr.touch && !window.location.hash) {
        $(window).load(function() {
            setTimeout(function() { window.scrollTo(0, 1); }, 0);
        });
    }

    // Initialize the Fancybox shizzle, if present.
    if(jQuery().fancybox) {
        $('.fancybox, div.imageholder a').fancybox({ });
    }

})(jQuery, this);

// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }
