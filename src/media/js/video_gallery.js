$(document).ready(function() {
    if (jQuery().magnificPopup) {
        $('.video-gallery__item a').magnificPopup({
            type: 'iframe',
        });
    }
});
