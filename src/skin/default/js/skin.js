$(document).ready(function() {

    // FrankenMenu
    if(jQuery().frankenMenu) {
        $("#frankenmenu").frankenMenu({
            menuBreakpoint: 768,
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

    // Select placeholder effect
    $("option.dropdown-top:selected").closest(".field-element").addClass("field-element--dropdown--placeholder");

    $("option.dropdown-top").closest("select").change(function(){
        var placeholderSelected = $("option:selected", this).hasClass("dropdown-top");
        if(!placeholderSelected) {
            $(this).closest(".field-element").removeClass("field-element--dropdown--placeholder");
        } else if(placeholderSelected){
            $(this).closest(".field-element").addClass("field-element--dropdown--placeholder");
        }
    });
});
