$(document).ready(function() {
    $('select[name=manipulate]').change(function() {
        var transform = $(this).val() ? $(this).val() : 'none';
        $('#manipulate-preview').attr('src', SITE + 'admin/call/file/previewTransform/' + transform + '/' + $(this).data('src'));
    });


    // Initialise focal point
    var img_scale;
    (function() {
        var pos = $('#image-focal-point').val().split(/,\s*/);
        var x = parseInt(pos[0], 10) + 0;
        var y = parseInt(pos[1], 10) + 0;

        // Determine relative (shrunken) position
        var img_copy = new Image();
        img_copy.src = $('#focal-point-setter').attr('src');

        // The image shown is shrunk down to 800px wide, so recalculate
        // the position of the focal point in the full sized image
        var shrunken_width = Math.min(800, img_copy.width);
        img_scale = img_copy.width / shrunken_width;

        x = x / img_scale;
        y = y / img_scale;

        if (x > 0 && y > 0) set_focal_point(x, y);
    })();


    /**
     * Sets the value of the hidden field to store focal point coordinates,
     * and updates the position of the focal point marker on the image
     *
     * @param Number x X-position (in px), between 1 and 800
     * @param Number y Y-position (in px), greater than 0
     */
    function set_focal_point(x, y) {
        $('#focal-point-dot').css('left', (x - 3) + 'px');
        $('#focal-point-dot').css('top', (y - 3) + 'px');
        $('#focal-point-dot').css('display', 'block');

        // Convert back to full dimensions for saving
        x = Math.round(x * img_scale);
        y = Math.round(y * img_scale);
        $('#image-focal-point').val(x + ', ' + y);
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

        if ($focal_point_dot === null) {
            $focal_point_dot = $('<div id="#focal-point-dot"></div>');
        }
    });
});
