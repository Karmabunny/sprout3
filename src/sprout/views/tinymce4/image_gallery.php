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
use Sprout\Helpers\Form;


$data = [];
$data['captions'] = (int) @$_GET['captions'];
$data['thumbs'] = (int) @$_GET['thumbs'];
$data['max'] = (int) @$_GET['limit'];
$data['cat'] = (int) @$_GET['cat'];
$data['crop'] = @$_GET['crop'];
$data['order'] = (int) @$_GET['order'];
$data['display_opts'] = @$_GET['display_opts'];
$data['slider_dots'] = (int) @$_GET['slider_dots'];
$data['slider_arrows'] = (int) @$_GET['slider_arrows'];
$data['slider_autoplay'] = (int) @$_GET['slider_autoplay'];
$data['slider_speed'] = (int) @$_GET['slider_speed'];

Form::setData($data);

$category_name = '';
foreach ($categories as $category) {
    if ($data['cat'] == $category['id']) {
        $category_name = $category['name'];
        break;
    }
}

$order_opts = [
    1 => 'Date (most recent at top)',
    2 => 'Date (oldest at top)',
    3 => 'Alphabetical by name',
    4 => 'Alphabetical (reverse)',
    5 => 'Manual (in category options)',
    6 => 'Stable random',
    7 => 'True random',
];

$crop_opts = [
    'lt' => 'Top left',
    'ct' => 'Top center',
    'rt' => 'Top right',
    'lc' => 'Middle left',
    'cc' => 'Middle center',
    'rc' => 'Middle right',
    'lb' => 'Bottom left',
    'cb' => 'Bottom center',
    'rb' => 'Bottom right',
];

$caption_opts = [
    '0' => 'No',
    '1' => 'Yes',
];

$type_opts = [
    'grid' => 'Gallery',
    'slider' => 'Slider',
];

$thumb_opts = [
    '4' => '4',
    '5' => '5',
];
?>

<link href="ROOT/sprout/media/css/admin_layout.css" rel="stylesheet">

<div class="emu-mce-wrapper">
    <div class="field-group-wrap -clearfix">
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Max number of images to show', false);
            echo Form::text('max', ['class' => 'js-gallery-max']);
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Display order', false);
            echo Form::dropdown('order', ['class' => 'js-ordering'], $order_opts);
            ?>

        </div>
    </div>
    <div class="field-group-wrap -clearfix">
        <div class="field-group-item col col--one-third">
            <?php
            Form::nextFieldDetails('Show captions?', false);
            echo Form::dropdown('captions', ['class' => 'js-gallery-captions'], $caption_opts);
            ?>
        </div>
        <div class="field-group-item col col--one-third">
            <?php
            Form::nextFieldDetails('Cropping anchor', false);
            echo Form::dropdown('crop', ['class' => 'js-gallery-crop'], $crop_opts);
            ?>
        </div>
        <div class="field-group-item col col--one-third">
            <?php
            Form::nextFieldDetails('Gallery type', false);
            echo Form::dropdown('display_opts', ['class' => 'js-gallery-type'], $type_opts);
            ?>
        </div>
    </div>

    <div class="field-group-wrap -clearfix js-gallery-grid">
        <div class="field-group-item">
            <?php
            Form::nextFieldDetails('Thumbnails per row', false);
            echo Form::dropdown('thumbs', ['class' => 'js-gallery-thumbs'], $thumb_opts);
            ?>
        </div>
    </div>

    <div class="field-group-wrap -clearfix js-gallery-slider">
        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php
                Form::nextFieldDetails('Slider options', false);
                echo Form::checkboxList(['slider_dots' => 'Dots', 'slider_arrows' => 'Arrows', 'slider_autoplay' => 'Auto-scroll'], []);
                ?>
            </div>
            <div class="field-group-item col col--one-half">
                <?php
                Form::nextFieldDetails('Auto-scroll timer', false, 'Seconds');
                echo Form::number('slider_speed', []);
                ?>
            </div>
        </div>
    </div>

</div>

<hr>

<div class="info">
    <?php if (!empty($category_name)): ?>Currently chosen <strong><?php echo Enc::html($category_name); ?></strong>.<?php endif; ?>
    Choose a category of images from the gallery below to save changes.
</div>

<div class="gallery-wrap">

<?php foreach ($categories as $category): ?>
    <?php
    $thumbs = explode('|', $category['filenames']);
    $index = 0;
    ?>

    <a href="javascript:;" data-id="<?php echo Enc::html($category['id']); ?>" data-title="<?php echo Enc::html($category['name']); ?>" class="<?php if ($category['id'] == $data['cat']): echo 'active'; endif; ?>">
        <div class="multi-thumb">

        <?php foreach ($thumbs as $filename): ?>
            <?php if (File::exists($filename)): ?>
                <img src="<?php echo File::resizeUrl($filename, 'c102x102'); ?>" class="idx <?php echo Enc::html($index); ?>">
                <?
                $index++;
                if ($index == 3) break;
                ?>
            <?php endif; ?>
        <?php endforeach; ?>

        </div>
        <div class="name"><?php echo Enc::html($category['name']); ?></div>
    </a>
<?php endforeach; ?>

    <div style="clear: both;"></div>
</div>

<script>

$(document).ready(function() {
    // Category thumb match height
    if (jQuery().matchHeight) {
        $(".gallery-wrap > a").matchHeight();
    }


    // Category thumb click event
    $("a[data-id]").click(function() {
        // Get form values
        var thumbs = parseInt($('select.js-gallery-thumbs').val());
        var captions = parseInt($('select.js-gallery-captions').val());
        var max = parseInt($('input.js-gallery-max').val());
        var crop = $('select.js-gallery-crop').val();
        var cat = parseInt($(this).attr('data-id'));
        var display_opts = $('select.js-gallery-type').val();
        var order_opts = parseInt($('select.js-ordering').val());
        var slider_dots = parseInt($('input[name="slider_dots"]').val());
        var slider_arrows = parseInt($('input[name="slider_arrows"]').val());
        var slider_autoplay = parseInt($('input[name="slider_autoplay"]').val());
        var slider_speed = parseInt($('input[name="slider_speed"]').val());

        if (isNaN(thumbs) || thumbs <= 0) thumbs = 5;
        if (isNaN(captions)) captions = 0;
        if (isNaN(max) || max <= 0) max = 100;
        if (crop == '') crop = 'cc';
        if (isNaN(cat) || cat < 0) cat = 0;
        if (display_opts == '') display_opts = 'grid';
        if (isNaN(order_opts) || order_opts <= 0) order_opts = 1;
        if (isNaN(slider_dots) || slider_dots < 0) slider_dots = 0;
        if (isNaN(slider_arrows) || slider_arrows < 0) slider_arrows = 0;
        if (isNaN(slider_autoplay) || slider_autoplay < 0) slider_autoplay = 0;
        if (isNaN(slider_speed) || slider_speed <= 0) slider_speed = 3;

        // Use form values as data-attributes for Widget's settings
        var content = '<div class="sprout-editor--widget sprout-editor--gallery" data-id="' + cat + '"';
        content += ' data-max="' + max + '"';
        content += ' data-captions="' + captions + '"';
        content += ' data-crop="' + crop + '"';
        content += ' data-thumbs="' + thumbs + '"';
        content += ' data-type="' + display_opts + '"';
        content += ' data-ordering="' + order_opts + '"';
        content += ' data-slider-dots="' + slider_dots + '"';
        content += ' data-slider-arrows="' + slider_arrows + '"';
        content += ' data-slider-autoplay="' + slider_autoplay + '"';
        content += ' data-slider-speed="' + slider_speed + '"';
        content += '>' + $(this).attr('data-title') + ' Gallery</div>';

        // Update or insert new div
        var elem = top.tinymce.activeEditor.selection.getNode();
        if (elem.nodeName == 'DIV' && $(elem).hasClass('sprout-editor--gallery')) {
            var nu = document.createElement('div');
            nu.innerHTML = content;
            top.tinymce.activeEditor.dom.replace(nu.firstChild, elem);
        } else {
            top.tinymce.activeEditor.selection.setContent(content, {'format': 'raw'});
        }

        TinyMCE4.closePopup();
    });

    // Gallery type drop-down event handler
    $('.js-gallery-type').on('change', function() {
        if ($(this).val() == 'grid') {
            $('.js-gallery-grid').show();
            $('.js-gallery-slider').hide();
        } else if ($(this).val() == 'slider') {
            $('.js-gallery-grid').hide();
            $('.js-gallery-slider').show();
        } else {
            $('.js-gallery-grid').hide();
            $('.js-gallery-slider').hide();
        }
    });

    $('.js-gallery-type').change();
});

</script>
