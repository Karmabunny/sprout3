function fb_datepicker_dropdowns($outer) {
    var $hid = $outer.find('input.hid');

    // Handle changes in value
    $outer.find('select').change(function() {
        var val = '';

        val += $outer.find('.wy').val();
        val += '-';
        val += $outer.find('.wm').val();
        val += '-';
        val += $outer.find('.wd').val();

        $hid.val(val).change();
    });

    // Initial value
    if ($hid.val()) {
        var parts = $hid.val().split('-', 3);
        $outer.find('.wy').val(parts[0]);
        $outer.find('.wm').val(parts[1]);
        $outer.find('.wd').val(parts[2]);
    }
}
