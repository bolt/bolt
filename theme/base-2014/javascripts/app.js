$(document).ready(function() {
    $(document).foundation();
});

// Initialize the Magnific popup shizzle. Fancybox is still here as a trigger, for backwards compatibility.
$('.magnific, .fancybox, div.imageholder a').magnificPopup({
    type: 'image',
    gallery: { enabled: true },
    disableOn: 400,
    closeBtnInside: true,
    enableEscapeKey: true,
    mainClass: 'mfp-with-zoom',
    zoom: {
        enabled: true,
        duration: 300,
        easing: 'ease-in-out'
    }
});

// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }
