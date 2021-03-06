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
use Sprout\Helpers\Needs;


Needs::fileGroup('magnific_popup');
Needs::fileGroup('image_gallery_wide');

// Create unique identifier to allow multiple on a page
$unique = md5(microtime(true));
?>

<?php if (!empty($images)): ?>
<div class="-clearfix image-gallery-cols image-gallery-col-<?php echo Enc::html($row_count); ?> <?php if ($captions): ?>image-gallery-has-captions<?php endif; ?>">
<?php foreach ($images as $image): ?>
    <?php if (!empty($image['filename']) and File::exists($image['filename'])): ?>
    <div class="image-gallery-thumb" <?php if ($idx++ > $num_thumbs): ?> style="display: none;" <?php endif; ?>>
        <a href="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['full_size'])); ?>"
            <?php if ($captions): ?> title="<?php echo Enc::html($image['name'] . ' - ' . $image['description']); ?>"<?php endif; ?>
            class="thumb gallery-<?php echo Enc::html($unique); ?>">
            <img src="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['thumb_size_' . Enc::html($row_count) . ''] . '-' . $cropping)) ?>" alt="<?php echo Enc::html($image['name']); ?>">
        </a>
        <?php if ($captions): ?>
        <p class="image-gallery-caption"><?php echo Enc::html($image['name']); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>

<script type="text/javascript">
if(jQuery().magnificPopup) {
    $('.gallery-<?php echo Enc::js($unique); ?>').magnificPopup({
        type: 'image',
        gallery: {
            enabled: true
        }
    });
}
</script>
<?php endif; ?>
