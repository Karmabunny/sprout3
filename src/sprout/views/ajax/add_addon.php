<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2015 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Enc;
?>
<style>
.add-widget-list {
    max-height: 500px;
    overflow-y: auto;
}
.add-widget-list .widget {
    padding: 10px;
    background: #f8f8f8;
    margin: 15px;
    min-height: 32px;
}
.add-widget-list .button {
    float: right;
    margin-left: 20px;
}
.add-widget-list .na-msg {
    margin-top: 10px;
    color: #964D4D;
}
</style>

<script>
$(document).ready(function() {
    $('.add-widget-list .button').click(function() {
        var $widget = $(this).closest('.widget');
        $('#wl-<?php echo Enc::js($field_name); ?>').trigger('add-widget', [$widget.attr('data-name'), $widget.find('b').text()]);
        $(document).trigger('close.facebox');
    });
});
</script>


<h3>Choose addon</h3>
<p>What type of addon would you like to add?</p>

<div class="add-widget-list">
    <?php
    foreach ($widgets as $row) {
        list($name, $eng_name, $desc, $not_available) = $row;

        echo '<div data-name="', Enc::html($name), '" class="widget">';

        if (!$not_available) {
            echo '<input type="button" class="button" value="Add this">';
        }

        echo '<p><b>', Enc::html($eng_name), '</b></p>';

        if ($desc) {
            echo '<div class="desc">', Enc::html($desc), '</div>';
        }

        if ($not_available) {
            echo '<div class="na-msg">', Enc::html($not_available), '</div>';
        }

        echo '</div>';
        echo PHP_EOL;
    }
    ?>
</div>
