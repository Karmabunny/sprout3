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


Needs::module('magnific_popup');
Needs::module('slick');
Needs::module('image_gallery_slider');

// Create unique identifier to allow multiple on a page
$unique = md5(microtime(true));
?>

<?php if (!empty($images) and count($images) > 0): ?>
<ul class="image-gallery-slider js--<?php echo Enc::html($unique); if ($captions): ?> image-gallery-has-captions<?php endif; ?>">
<?php foreach ($images as $image): ?>
    <?php if (!empty($image['filename']) and File::exists($image['filename'])): ?>
    <li class="image-gallery-slider__item">
        <a href="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['full_size'])); ?>"
        <?php if ($captions): ?> title="<?php echo Enc::html($image['name'] . ' - ' . $image['description']); ?>"<?php endif; ?>
        class="image-gallery-slider__link gallery-<?php echo Enc::html($unique); ?>">
            <img src="<?php echo Enc::html(File::resizeUrl($image['filename'], $config['slider_size'] . '-' . $cropping)) ?>" alt="<?php echo Enc::html($image['name']); ?>">
        </a>
        <?php if (!empty($captions)): ?>
        <p class="image-gallery-caption"><?php echo Enc::html($image['name']); ?></p>
        <?php endif; ?>
    </li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>

<script>
$(document).ready(function() {
    var unique = '<?php echo Enc::js($unique); ?>';

    if(jQuery().magnificPopup) {
        $('.gallery-<?php echo Enc::js($unique); ?>').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            }
        });
    }
    if (jQuery().slick) {
        $('.image-gallery-slider.js--' + unique).slick({
            dots: <?php echo Enc::js(!empty($slider_dots)?'true':'false'); ?>,
            arrows: <?php echo Enc::js(!empty($slider_arrows)?'true':'false'); ?>,
            autoplay: <?php echo Enc::js(!empty($slider_autoplay)?'true':'false'); ?>,
            autoplaySpeed: <?php echo Enc::js($slider_speed * 1000); ?>,
        });
    }
});
</script>
<?php endif; ?>
