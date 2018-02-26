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

Form::setData($data);

$category_name = '';
foreach ($categories as $category) {
    if ($data['cat'] == $category['id']) {
        $category_name = $category['name'];
        break;
    }
}
?>

<script>
$(document).ready(function() {
    $(".gallery-wrap > a").matchHeight();
});
</script>

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
            Form::nextFieldDetails('Thumbnails per row', false);
            echo Form::dropdown('thumbs', ['class' => 'js-gallery-thumbs'], ['4' => '4', '5' => '5']);
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Show captions?', false);
            echo Form::dropdown('captions', ['class' => 'js-gallery-captions'], ['0' => 'No', '1' => 'Yes']);
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Cropping anchor', false);
            echo Form::dropdown('crop', ['class' => 'js-gallery-crop'], ['lt' => 'Top left','ct' => 'Top center','rt' => 'Top right','lc' => 'Middle left','cc' => 'Middle center','rc' => 'Middle right','lb' => 'Bottom left','cb' => 'Bottom center','rb' => 'Bottom right']);
            ?>
        </div>
    </div>
</div>

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
    $("a[data-id]").click(function() {
        // Get form values
        var thumbs = parseInt($('select.js-gallery-thumbs').val());
        var captions = parseInt($('select.js-gallery-captions').val());
        var max = parseInt($('input.js-gallery-max').val());
        var crop = $('select.js-gallery-crop').val();
        var cat = parseInt($(this).attr('data-id'));

        if (isNaN(thumbs) || thumbs <= 0) thumbs = 5;
        if (isNaN(captions)) captions = 0;
        if (isNaN(max) || max <= 0) max = 100;
        if (crop == '') crop = 'cc';
        if (isNaN(cat) || cat < 0) cat = 0;

        var elem = top.tinymce.activeEditor.selection.getNode();

        // Remove existing place-holder
        if (elem.nodeName == 'DIV' && $(elem).hasClass('sprout-editor--gallery')) {
            top.tinymce.activeEditor.dom.remove(elem);
        }

        // Use form values as data-attributes for Widget's settings
        var content = '<div class="sprout-editor--widget sprout-editor--gallery" data-id="' + cat + '"';
        content += ' data-max="'+ max + '"';
        content += ' data-captions="' + captions + '"';
        content += ' data-crop="' + crop + '"';
        content += ' data-thumbs="' + thumbs + '"';
        content += ' >' + $(this).attr('data-title') + ' Gallery</div>';

        // Insert new place-holder
        top.tinymce.activeEditor.selection.setContent(content, {format: 'raw'});
        TinyMCE4.closePopup();
    });
});
</script>
