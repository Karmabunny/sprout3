$(document).ready(function() {

    // Submit button - disable on click
    $('form').submit(function(){
        $('input[type=submit]:not(.no-disable),button[type=submit]:not(.no-disable)', this).each(function() {
            $(this).attr('disabled', 'disabled');
        });
    });

    // Hack to copy name,value pair from <input|button type="submit"> into hidden input
    // This prevents loss of the name,value pair on form submit (due to the disable on click code above)
    $('input[type=submit]:not(.no-disable),button[type=submit]:not(.no-disable)').click(function() {
        if ($(this).attr('name')) {
            var $input = $('<input type="hidden">');
            $input.attr('name', $(this).attr('name'));
            $input.attr('value', $(this).attr('value'));
            $(this).closest('form').append($input);
        }
    });

    // Error class on inputs before a field-error
    $('span.field-error').each(function() {
        $(this).parent().find('input,select,textarea').addClass('field-error');
        $(this).parent().next('td.field-info').prepend(this);
    });


    $('input[title]').each(function() {
        // on focus - make text empty if it is set to emptytext
        $(this).focus(function() {
            if ($(this).attr('value') == $(this).attr('title')) {
                $(this).attr('value', '').removeClass('placeholder-text');
            }
        });

        // on blur - make text emptytext if it is empty
        $(this).blur(function() {
            if ($(this).attr('value') == '') {
                $(this).attr('value', $(this).attr('title')).addClass('placeholder-text');
            }
        });

        // run the blur event
        $(this).blur();
    });

    // on submit - make text empty if it is set to emptytext
    $('input[title]').closest('form').submit(function() {
        $(this).find('input[title]').each(function() {
            if ($(this).attr('value') == $(this).attr('title')) {
                $(this).attr('value', '');
            }
        });
    });


    // Expando divs
    $('div.expando').each(function() {
        var $div = $(this);
        var $expander = $(this).prev('h2,h3');
        var $close = $('<button type="button" class="expando__close">Close</button>');

        $close.appendTo($div);

        if ($expander.length == 0) {
            $expander = $(
                '<button type="button" class="expando-open expando-open--button">'
                + '<span class="expando-open__icon"></span>'
                + ($(this).attr('title') ? $(this).attr('title') : 'More information')
                + '</button>'
            );
            $expander.insertBefore(this);
        } else if ($expander.is('h2,h3')) {
            $expander.addClass('expando-open expando-open--heading');
            $expander.prepend($('<span class="expando-open__icon"></span>'));
        }

        $expander.add($close).click(function() {
            $div.toggleClass('expando--expanded');
            $expander.toggleClass("expando-open--expanded");
            return false;
        });

    });


    // Refine bar
    $(".refine-advanced-button").click(function(){
        $(this).siblings(".refine-list").toggleClass("refine-list-advanced-visible");
        $(this).toggleClass("icon-keyboard_arrow_up icon-keyboard_arrow_down");
    });

});


/**
* Load a JavaScript or CSS file, but only if it's not currently loaded
*
* @param string tag A full HTML tag to load, either a <script> tag or a <link> tag
**/
function dynamicNeedsLoader(tag)
{
    console && console.log('Dynamic <needs/> loading: ', tag);

    var $tmp = $(tag);

    var $srch = null;
    if (tag.match(/^<script/i)) {
        $srch = $('script[src="' + $tmp.attr('src') + '"]');
    } else if (tag.match(/^<link/i)) {
        $srch = $('link[href="' + $tmp.attr('href') + '"][rel="' + $tmp.attr('rel') + '"]');
    } else {
        return true;
    }

    if ($srch.length == 0) {
        $('head').append($tmp);
    }
}
