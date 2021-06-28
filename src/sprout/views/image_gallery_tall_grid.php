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

// Create unique identifier to allow multiple on a page
$unique = md5(microtime(true));
?>

<?php if (!empty($images) and count($images) > 0): ?>
<div class="-clearfix image-gallery-side-wrap <?php if ($captions): ?>image-gallery-side-wrap--has-captions<?php endif; ?>">
<?php foreach ($images as $image): ?>
    <?php if (!empty($image['filename']) and File::exists($image['filename'])): ?>
    <div class="image-gallery-side-thumb" <?php if ($idx++ > $num_thumbs): ?> style="display: none;" <?php endif; ?>>
        <a href="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['full_size'])); ?>"
            <?php if ($captions): ?> title="<?php echo Enc::html($image['name'] . ' - ' . $image['description']); ?>"<?php endif; ?>
            class="image-gallery-side-thumb__link gallery-<?php echo Enc::html($unique); ?>">
            <img src="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['thumb_size_5'] . '-' . $cropping)) ?>" alt="<?php echo Enc::html($image['name']); ?>">
        </a>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

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
