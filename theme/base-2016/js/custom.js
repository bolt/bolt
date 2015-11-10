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
});
document.querySelector("#menu-icon").addEventListener("click", function() {
    document.querySelector("ul.menu").classList.toggle("show");
    document.querySelector("#menu-icon").classList.toggle("active");
});
