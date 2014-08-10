$(document).ready(function() {
    $(document).foundation();
});

// Initialize the Fancybox shizzle, if present.
if(jQuery().fancybox) {
    $('.fancybox, div.imageholder a').fancybox({ });
}

// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }
