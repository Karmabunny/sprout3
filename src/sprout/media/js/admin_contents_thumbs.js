/**
* Thumbnail select
**/
$(document).ready(function() {

    $(".file-thumbs .selection input").change(function(){
        $this = $(this);
        if($this.is(":checked")) {
            $this.closest(".thumb").addClass("thumb-selected");
        } else if(!$this.is(":checked")) {
            $this.closest(".thumb").removeClass("thumb-selected");
        }
    });

});
