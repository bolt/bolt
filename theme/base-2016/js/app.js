$(document).foundation();

$('.magnific, .imageholder a').magnificPopup({
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
