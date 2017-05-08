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
use Sprout\Helpers\File;
?>
<script>
$(document).ready(function() {
    $(".gallery-wrap > a").matchHeight();
});
</script>

<?php
echo $toolbar;
?>

<div class="info">
    Choose a category of images from the gallery below.
</div>

<div class="gallery-wrap">

    <?php
    foreach ($categories as $row) {
        $thumbs = explode('|', $row['filenames']);

        echo '<a href="SITE/tinymce4/image_list/', $row['id'], '">';
        echo '<div class="multi-thumb">';

        $index = 0;
        foreach ($thumbs as $filename) {
            if (File::exists($filename)) {
                echo '<img src="', File::resizeUrl($filename, 'c102x102'), '" class="idx' . $index . '">';
                $index++;
                if ($index == 3) break;
            }
        }

        echo '</div>';
        echo '<div class="name">', Enc::html($row['name']), '</div>';
        echo '</a>';
    }
    ?>

    <div style="clear: both;"></div>
</div>
