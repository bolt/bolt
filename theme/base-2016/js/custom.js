$(document).ready(function () {
    // Magnific image popup with zoom
    $('.magnific, div.imageholder a').magnificPopup({
        type: 'image',
        gallery: {
            enabled: true
        },
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

    // Menu JS
    $("#menu-icon").on("click", function () {
        $("ul.menu").slideToggle();
        $(this).toggleClass("active");
    });
});
