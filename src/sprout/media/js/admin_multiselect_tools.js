/**
* Clicking on checkboxes to show/hide the additional tools
**/
$(document).ready(function() {
    var tagUI;
    var tagURL;


    // UI for the tags popup
    tagUI = '<h2 class="popup-title">Add tags</h2>'
        + '<form class="popup-dummy-form" action="javacript:;" method="get">'
        + '<div class="field-element field-element--text">'
        + '    <div class="field-label">'
        + '        <label for="tags">Tags</label>'
        + '        <div class="field-helper">Enter commas between tags</div>'
        + '    </div>'
        + '    <div class="field-input">'
        + '        <input id="tags" class="textbox" type="text" name="tags">'
        + '    </div>'
        + '</div>'
        + '<div class="text-align-right"><button type="submit" class="button">Add tags</button>'
        + '</form>';



    // Tag link
    $('.multiple-add-tag').click(function(e) {
        tagURL = $(this).attr('href');
        $.facebox(tagUI, 'multiselect-popup');

        $('.multiselect-popup .popup-dummy-form').submit(tag_action);

        e.stopImmediatePropagation();
        return false;
    });

    // Tag action. Return true on success, false on error
    function tag_action() {
        $.post(tagURL, $('form.selection-action').serialize() + '&' + $('form.popup-dummy-form').serialize(), function(data) {
            if (data.success == 0) {
                alert(data.message);
            } else {
                close_box_with_msg('Items tagged successfully');
            }
        }, 'json');

        return false;
    }

    // Close message
    function close_box_with_msg(msg) {
        $('.multiselect-popup').append('<div class="message">' + msg + '</div>');
        $('.multiselect-popup div:not(.message)').slideUp();
        $('.multiselect-popup .message').slideDown().animate({opacity: 1}, 1000, function() {
            $(document).trigger('close.facebox');
        });
    }


    // Other links
    $('a.selection-action').click(function() {
        $('form.selection-action').attr('action', $(this).attr('href')).submit();
        return false;
    });

    // If one or more ticked, show the links box
    $('.selection input, .selection-all input').change(function() {
        if ($('.selection input:checked').length) {
            $('.selected-tools').show();
        } else {
            $('.selected-tools').hide();
        }
    });

});
