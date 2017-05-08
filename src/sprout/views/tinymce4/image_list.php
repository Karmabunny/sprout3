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
    Choose an image from the gallery below.
</div>

<?php
if (!empty($up_url)) {
    echo '<ul class="link-list"><li class="up"><a href="', Enc::html($up_url), '">Up one level</a></li></ul>';
}
?>

<div class="gallery-wrap">

    <?php foreach ($images as $row): ?>
        <?php if (File::exists($row['filename'])): ?>

            <a href="SITE/tinymce4/image_size/<?php echo $row['id'], '?', http_build_query($link_attrs); ?>">
                <div class="single-thumb">
                    <img src="<?php echo File::resizeUrl($row['filename'], 'c102x102'); ?>">
                </div>
                <div class="name"><?php echo Enc::html($row['name']); ?></div>
            </a>

        <?php endif; ?>
    <?php endforeach; ?>

    <div style="clear: both;"></div>
</div>
