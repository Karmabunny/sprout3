// Error animation
$(document).ready(function(){
    if($(".login-box-content .messages.all-type-error").length){
        $("html").addClass("login-box-error");
    }
});

// Error animation
$(document).ready(function(){
    $(".login-form").submit(function(){
        $("html").addClass("login-box-loading");
        setTimeout(function(){
            $("html").addClass("login-box-loading-spinner");
        }, 400);
    });
});