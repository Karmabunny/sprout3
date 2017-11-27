$(document).ready(function() {
    $('select[name=manipulate]').change(function() {
        var transform = $(this).val() ? $(this).val() : 'none';
        $('#manipulate-preview').attr('src', SITE + 'admin/call/file/previewTransform/' + transform + '/' + $(this).data('src'));
    });

    /**
     * list of orientation => focal point mappings
     *
     * each focal point is an array: [x, y] in px values relative to the full-size image
     */
    var focal_points = {};

    /** one of 'default', 'landscape', etc. */
    var current_focal_point = 'default';

    /** ratio of full-size image to shrunken (800px) image displayed on screen */
    var img_scale;

    $('#focal-point-type-selector li').click(function() {
        current_focal_point = $(this).data('type');
        $('#focal-point-preview').hide();
        $('#focal-point-type-selector li').removeAttr('data-active');
        $(this).attr('data-active', 1);

        var pos = [0, 0];
        if (focal_points[current_focal_point]) {
            pos = focal_points[current_focal_point];
        }
        display_focal_point(pos[0] / img_scale, pos[1] / img_scale);
    });


    // Initialise focal point
    (function() {
        var positions_json = $('#image-focal-points').val();
        var positions = {};
        var x = 0;
        var y = 0;

        if (positions_json) {
            focal_points = $.parseJSON(positions_json);
        }

        if (focal_points[current_focal_point]) {
            x = focal_points[current_focal_point][0];
            y = focal_points[current_focal_point][1];
        }

        // Determine relative (shrunken) position
        var img_copy = new Image();

        // The image shown is shrunk down to 800px wide, so recalculate
        // the position of the focal point in the full sized image
        // Note - width is only available after the image has loaded
        img_copy.onload = function() {
            var shrunken_width = Math.min(800, img_copy.width);
            img_scale = img_copy.width / shrunken_width;

            x = x / img_scale;
            y = y / img_scale;

            if (x > 0 && y > 0) display_focal_point(x, y);
        };

        img_copy.src = $('#focal-point-setter').attr('src');
    })();


    /**
     * Sets the value of the hidden field to store focal point coordinates for the current variant
     *
     * @param Number x X-position (in px), between 1 and 800
     * @param Number y Y-position (in px), greater than 0
     */
    function set_focal_point(x, y) {
        x = Math.round(x * img_scale);
        y = Math.round(y * img_scale);
        focal_points[current_focal_point] = [x, y];
        $('#image-focal-points').val(JSON.stringify(focal_points));
    }


    /**
     * Updates the position of the focal point marker on the image
     *
     * @param Number x X-position (in px), between 1 and 800
     * @param Number y Y-position (in px), greater than 0
     */
    function display_focal_point(x, y) {
        $('#focal-point-dot').css('left', (x - 3) + 'px');
        $('#focal-point-dot').css('top', (y - 3) + 'px');
        if (x > 0 && y > 0) {
            $('#focal-point-dot').css('display', 'block');
        } else {
            $('#focal-point-dot').css('display', 'none');
        }

        if (current_focal_point != 'default') {
            var size = $('#focal-point-type-selector li[data-type="' + current_focal_point + '"]').data('size');
            var filename = $('#manipulate').data('src');
            var points = $('#image-focal-points').val();
            if (points == '') points = '[]';
            points = encodeURIComponent(points);
            var url = 'admin/call/file/previewFocalCrop/' + size + '/' + filename + '/' + points;
            url += '?r=' + Math.round(10000 + (Math.random() * 99000.0));
            var $html = $('<img alt="">');
            $html.attr('src', url);
            $('#focal-point-preview-image').html($html);
            $('#focal-point-preview').show();
        } else {
            $('#focal-point-preview-image').html('');
            $('#focal-point-preview').hide();
        }
    }


    // Handle click to produce new focal point
    var $focal_point_dot = null;
    $('#focal-point-setter').click(function(e) {
        var offset = $(this).offset();
        var pos = {
            left: e.pageX - offset.left,
            top: e.pageY - offset.top
        };

        set_focal_point(pos.left, pos.top);
        display_focal_point(pos.left, pos.top);

        if ($focal_point_dot === null) {
            $focal_point_dot = $('<div id="#focal-point-dot"></div>');
        }
    });
});
