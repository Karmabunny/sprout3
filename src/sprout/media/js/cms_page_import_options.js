$(document).ready(function() {
    /**
    * Type change - show or hide sction
    **/
    $('input[name=import_type]').change(function() {
        var val = $('input[name=import_type]:checked').val();

        $('.import-type').hide();
        $('.import-type[data-type=' + val + ']').show();
    });

    $('input[name=import_type]').eq(0).triggerHandler('change');


    /**
    * Main form submit - kill off  fields from other sections
    **/
    $('#main-form').submit(function() {
        var val = $('input[name=import_type]:checked').val();
        $('.import-type').not('[data-type=' + val + ']').remove();
    });


    /**
    * Preview based on the options
    **/
    function preview() {
        $.get(ROOT + 'admin/call/page/importPreviewAjax', $('#main-form').serialize(), function(html) {
            if (html) {
                $('.preview').html('<h4>Preview</h4>' + html);
            } else {
                $('.preview').html('');
            }
        });
    }

    var previewThrottled = _.throttle(preview, 250, {leading:false});

    /**
    * Cute box shown on submit to the user so they don't get too bored
    **/
    function submitWait() {
        var $messageBox = $('<div class="message-box"><p>Your document is being imported.</p><p>Please wait, this may take several minutes.</p><p>Do not close your browser during the import process.</p></div>');

        var posX = ($(window).width() - $messageBox.outerWidth()) / 2;
        var posY = ($(window).height() - $messageBox.outerHeight()) / 2;
        $messageBox.css({position: 'fixed', left: posX, top: posY, opacity: 0});

        $('body').append($messageBox);

        $messageBox.animate({opacity: 1.0}, 500, function() {
            $('#main-form .save').animate({opacity: 0.3}, 1000);
        });

        return true;
    }


    $('input[name=import_type]').change(previewThrottled);
    $('input[name=page_name]').keypress(previewThrottled);
    $('select[name=heading_level]').change(previewThrottled);
    $('input[name=top_page_name]').keypress(previewThrottled);
    $('#main-form').submit(submitWait);
});
