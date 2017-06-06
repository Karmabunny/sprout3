$(document).ready(function() {
    if(jQuery().magnificPopup) {
        $('.video-gallery-list__item a').magnificPopup({
            type: 'iframe',
        });
    }
});
