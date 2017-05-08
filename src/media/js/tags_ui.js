/**
* Does a tag suggestion lookup
**/
function suggestion_lookup(table, tag) {
    $.getJSON('admin_ajax/get_tag_suggestions/' + table, {'prefix': tag}, function(data) {
        var i;

        var html = '';
        for (i = 0; i < data.length; i++) {
            html += '<a href=\"#\">' + data[i] + '</a>';
        }
        $('p.tags-suggest').html(html);

        suggestion_highlight();
    });
}

/**
* Checks which tags exist in the string, and marks them as such in the common tags area
**/
function suggestion_highlight() {
    var val = $('#tags-text').val();

    $('p.tags-suggest a').each(function() {
        if (val.match(new RegExp('(^|,)( *)' + $(this).text() + '( *)(,|$)'))) {
            $(this).addClass('selected');
        } else {
            $(this).removeClass('selected');
        }
    });
}

/**
* Returns the last tag in the current tag field
**/
function get_lasttag() {
    var val = $('#tags-text').val();

    var lasttag = val.match(/(^|,) *([^,]+)$/);
    if (lasttag == null) return null;

    lasttag[2] = lasttag[2].replace(/^ +/, '').replace('/ +$/', '');

    return lasttag[2];
}


$(document).ready(function() {
    var timeout;

    // Clicking on a suggestion link
    $('p.tags-suggest a').click(function() {
        var val = $('#tags-text').val();
        var tag = $(this).text();

        var lasttag = get_lasttag();

        var curr = val.split(',');

        $.each(curr, function (i) {
            curr[i] = $.trim(curr[i]);
            if (curr[i] == '') delete(curr[i]);
        });

        if ($.inArray(tag, curr) != -1) {
            val = val.replace(tag, '');
            $(this).removeClass('selected');

        } else if (lasttag && tag.indexOf(lasttag) == 0) {
            val += tag.substr(lasttag.length);
            $(this).addClass('selected');
            suggestion_lookup(table, '');

        } else {
            val += ', ' + tag;
            $(this).addClass('selected');

        }

        val = val.replace(/^[, ]+/, '').replace(/[, ]+$/, '').replace(/( *,+ *)+/g, ', ');
        if (val) val += ', ';

        $('#tags-text').val(val);
        $('#tags-text').focus();

        return false;
    });

    // Typing of a key
    $('#tags-text').keyup(function(event) {
        window.clearTimeout(timeout);
        suggestion_highlight();

        // Escape
        if (event.which == 27) {
            suggestion_lookup(table, '');
            return false;
        }

        var lasttag = get_lasttag();
        if (! lasttag) {
            suggestion_lookup(table, '');
            return true;
        }

        timeout = window.setTimeout("suggestion_lookup('" + table + "','" + lasttag + "')", 250);
    });
});
