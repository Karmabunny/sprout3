$(document).ready(function() {

    // FrankenMenu
    if(jQuery().frankenMenu) {
        $("#frankenmenu").frankenMenu({
            menuBreakpoint: 768,
            devMode: true
        });
    }

    // MagnificPopup links
    if(jQuery().magnificPopup) {
        $('a.js-popup-page').magnificPopup({
            type: 'ajax',
        });

        $('a.js-popup-image').magnificPopup({
            type: 'image',
        });
    }
});
