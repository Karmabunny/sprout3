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

use Sprout\Helpers\Admin;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Text;


Form::setData($data);
Form::setErrors($errors);

if ($data['type'] == FileConstants::TYPE_IMAGE) {
    Needs::fileGroup('sprout/image_edit');
}

$abs_url = File::absUrl($data['filename']);
?>

<div class="main-tabs">
    <ul>
        <li><a href="#main-tabs-details">Details</a></li>
        <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
            <li><a href="#main-tabs-manipulate">Manipulate image</a></li>
            <li><a href="#main-tabs-focus">Set focal points</a></li>
        <?php endif; ?>
        <li><a href="#main-tabs-replace">Replace file</a></li>
        <li><a href="#main-tabs-cats">Categories</a></li>
    </ul>

    <div id="main-tabs-details">

        <div class="clear-group">
            <div class="col col--one-half">
                <?php Form::nextFieldDetails('Name', true); ?>
                <?= Form::text('name'); ?>
            </div>

            <div class="col col--one-half">
                <?php Form::nextFieldDetails('Author', false); ?>
                <?= Form::autocomplete('author', [], ['url' => 'admin/call/file/ajaxAuthorLookup', 'save_id' => false]); ?>
            </div>
        </div>

        <?php Form::nextFieldDetails('Description', false); ?>
        <?= Form::multiline('description', ['rows' => 5, 'cols' => 40]); ?>

        <?php if ($data['type'] == FileConstants::TYPE_DOCUMENT): ?>
        <div class="clear-group">
            <div class="col col--one-half">
                <?php
                Form::nextFieldDetails('Document type', false);
                echo Form::dropdown('document_type', [], $document_types);
                ?>
            </div>

            <div class="col col--one-half">
                <?php
                Form::nextFieldDetails('Date published', false);
                echo Form::datepicker('date_published');
                ?>
            </div>
        </div>
        <?php endif; ?>

        <h3>Details</h3>

        <table class="main-list">
            <thead><tr>
                <th>Filename</th>
                <th>URL</th>
                <th>Type</th>
                <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?><th>Image dimensions</th><?php endif; ?>
            </tr></thead>
            <tbody><tr>
                <td><?= Enc::html($data['filename']); ?></td>
                <td><a href="<?= Enc::html($abs_url ); ?>"><?= Enc::html(Text::limitChars($abs_url, 50)); ?></a></td>
                <td><?= Enc::html(FileConstants::$type_names[$data['type']]); ?></td>
                <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?><td><?= Enc::html($img_dimensions); ?></td><?php endif; ?>
            </tr></tbody>
        </table>




        <!-- Preview -->
        <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
            <h3>Preview</h3>
            <p>
                <a href="<?php echo Enc::html(File::absUrl($data['id']));; ?>" target="_blank">
                    <img src="<?php echo Enc::html(File::resizeUrl($data['id'], 'r200x0')); ?>" alt="preview">
                </a>
            </p>

            <?php if (!empty($sizes) and count($sizes)): ?>
            <h3>Available sizes</h3>

            <table class="main-list">
                <thead><tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Dimensions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($sizes as $resize): ?>
                    <?php
                    $abs_url = $resize['url'];
                    $dimensions = json_decode($resize['imagesize'], true);
                    $size = $resize['filesize'];
                    ?>

                    <tr>
                        <td><a href="<?= Enc::html($abs_url); ?>" target="_blank"><?= Enc::html($resize['size_filename']); ?></a></td>
                        <td><?= File::humanSize($size); ?></td>
                        <td><?= Enc::html(sprintf('%u x %u', $dimensions[0], $dimensions[1])); ?></td>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

        <?php elseif ($data['type'] == FileConstants::TYPE_DOCUMENT and $data['plaintext']): ?>
            <?= Fb::heading('Preview'); ?>
            <pre><?php echo Enc::html($preview); ?></pre>
        <?php endif; ?>
    </div>

    <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
        <div id="main-tabs-manipulate">
            <?= Fb::heading('Manipulate image'); ?>

            <div class="field-element field-element--select">
                <div class="field-label -vis-hidden">
                    <label for="manipulate">Current site</label>
                </div>
                <div class="field-input">
                    <select id="manipulate" name="manipulate" data-src="<?= Enc::html($data['filename']); ?>">
                        <option value="">Select an option</option>
                        <?php if (function_exists('imagerotate')): ?>
                        <option value="rotate-90-clockwise">Rotate 90&deg; clockwise</option>
                        <option value="rotate-90-counterclockwise">Rotate 90&deg; counter-clockwise</option>
                        <option value="rotate-180">Rotate 180&deg;</option>
                        <?php endif; ?>

                        <option value="flip-horizontal">Flip horizontal</option>
                        <option value="flip-vertical">Flip vertical</option>
                    </select>
                </div>
            </div>

            <div class="highlight">

                <div class="columns">
                    <div class="column column-6">
                        <p><strong>Original image:</strong></p>
                        <img src="<?= Enc::html($original_image); ?>" alt="">
                    </div>

                    <div class="column column-6">
                        <p><b>New image:</b></p>
                        <img src="SITE/admin/call/file/previewTransform/none/<?php echo Enc::html($data['filename']); ?>" alt="" id="manipulate-preview">
                    </div>
                </div>

            </div>
        </div>

        <div id="main-tabs-focus">
            <?= Fb::heading('Set focal points'); ?>

            <p>Click the position on the image where you want the focal point to be set.</p>
            <p>When the image is resized, the resizing will be done so that the focal point is always visible, and as close to the centre as possible.</p>
            <p>In most cases, you should only need to set a default focal point, to capture the important part of the
            image in all orientations. However, if you need more control, you can choose a different focal points for
            any particular orientations. Click an orientation and then click within the image to set a specific focal
            point for that orientation.</p>

            <ul id="focal-point-type-selector">
                <li data-type="default" data-active="1">Default</li>
                <li data-type="landscape" data-size="300x200">Landscape</li>
                <li data-type="portrait" data-size="200x300">Portait</li>
                <li data-type="square" data-size="200x200">Square</li>
                <li data-type="panorama" data-size="400x100">Panorama</li>
            </ul>

            <div id="focal-point-wrapper"><img id="focal-point-setter" src="<?php echo File::url($data['filename']); ?>"><div id="focal-point-dot"></div></div>

            <input type="hidden" id="image-focal-points" name="focal_points" value="<?= Enc::html(@$data['focal_points']); ?>">

            <div id="focal-point-preview" style="display: none;">
                <h3>Preview</h3>

                <p>Please note that this is just an example of the type of orientation, and does not represent how the image will look on your site.</p>

                <p><span id="focal-point-preview-image"></span></p>
            </div>
        </div>
    <?php endif; ?>

    <div id="main-tabs-replace">
        <?= Fb::heading('Replace file'); ?>

        <p>Replacement file must be of the type <b><?php echo FileConstants::$type_names[$data['type']]; ?></b>.</p>

        <?= Form::upload('replace'); ?>
    </div>

    <div id="main-tabs-cats">
        <?= Fb::heading('Categories'); ?>
        <?= Admin::categorySelection('categories[]', $cats, $data['categories']); ?>
    </div>
</div>


<?php Admin::clearFieldErrors(); ?>
