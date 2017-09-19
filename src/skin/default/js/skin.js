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

    // Responsive tables by luke
    $(".table--responsive").each(function(){
        var $this = $(this);
        var headings = [];
        var $headingRow = $this.find("thead").eq(0).find("tr").eq(0);
        var $bodyRows = $this.find("tbody tr");

        // If table is not properly formatted, assume first row is the thead
        if(!$headingRow.length) {
            $headingRow = $this.find("tr").eq(0);
            $headingRow.addClass("table--responsive__first-row");
            $bodyRows = $this.find("tbody tr:not(:first-child)");
        }

        $headingRow.find("th").each(function(){
            headings.push($(this).html());
        });

        $bodyRows.each(function(){
            $(this).find("td").each(function(i){
                $(this).attr("data-title", headings[i]);
                $(this).wrapInner("<div class='table--responsive__content'></div>")
            });
        });
    });

});
