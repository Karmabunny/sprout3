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


echo $toolbar;
?>

<div class="info">
    Choose the size you would like to insert the image <b><?php echo Enc::html($image['name']); ?></b>.
</div>

<?php
echo '<ul class="link-list">';

if ($up_url) {
    echo '<li class="up"><a href="', Enc::html($up_url), '">Up one level</a></li>';
}

foreach ($sizes as $size) {
    echo '<li class="size">';
    echo '<a href="javascript:;" data-src="', Enc::html(File::sizeUrl($image['id'], $size)), '" data-alt="', Enc::html($image['name']), '">', ucwords(str_replace('_', ' ', $size)), '</a>';
    echo '</li>';
}
echo '<li class="size">';
echo '<a href="javascript:;" data-src="', Enc::html(File::relUrl($image['id'])), '" data-alt="', Enc::html($image['name']), '">Original</a>';
echo '</li>';

echo '</ul>';
?>

<script>
$(document).ready(function(){
    $("a[data-src]").click(function(){
        TinyMCE4.setUrl($(this).attr("data-src"));
        TinyMCE4.setField("Image description", $(this).attr("data-alt"));
        TinyMCE4.setField("Dimensions", "");
        TinyMCE4.closePopup();
    });
})
</script>
