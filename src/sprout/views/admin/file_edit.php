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
?>

<script type="text/javascript">
$(document).ready(function() {
    $('select[name=manipulate]').change(function() {
        var transform = $(this).val() ? $(this).val() : 'none';
        $('#manipulate-preview').attr('src', 'SITE/admin/call/file/previewTransform/' + transform + '/<?php echo Enc::js($data['filename']); ?>');
    });


    // Initialise focal point
    var img_scale;
    (function() {
        var pos = $('#image-focal-point').val().split(/,\s*/);
        var x = parseInt(pos[0], 10) + 0;
        var y = parseInt(pos[1], 10) + 0;

        // Determine relative (shrunken) position
        var img_copy = new Image();
        img_copy.src = $('#focal-point-setter').attr('src');

        // The image shown is shrunk down to 800px wide, so recalculate
        // the position of the focal point in the full sized image
        var shrunken_width = Math.min(800, img_copy.width);
        img_scale = img_copy.width / shrunken_width;

        x = x / img_scale;
        y = y / img_scale;

        if (x > 0 && y > 0) set_focal_point(x, y);
    })();


    /**
     * Sets the value of the hidden field to store focal point coordinates,
     * and updates the position of the focal point marker on the image
     *
     * @param Number x X-position (in px), between 1 and 800
     * @param Number y Y-position (in px), greater than 0
     */
    function set_focal_point(x, y) {
        $('#focal-point-dot').css('left', (x - 3) + 'px');
        $('#focal-point-dot').css('top', (y - 3) + 'px');
        $('#focal-point-dot').css('display', 'block');

        // Convert back to full dimensions for saving
        x = Math.round(x * img_scale);
        y = Math.round(y * img_scale);
        $('#image-focal-point').val(x + ', ' + y);
    }


    // Handle click to produce new focal point
    var $focal_point_dot = null;
    $('#focal-point-setter').click(function(e) {
        var offset = $(this).offset();
        var pos = {
            left: e.pageX - offset.left,
            top: e.pageY - offset.top
        };

        set_focal_point(pos.left, pos.top);

        if ($focal_point_dot === null) {
            $focal_point_dot = $('<div id="#focal-point-dot"></div>');
        }
    });
});
</script>

<style type="text/css">
#focal-point-wrapper {
    position: relative;
}

    #focal-point-setter {
        max-width: 800px;
    }

    #focal-point-dot {
        display: none;
        width: 7px;
        height: 7px;
        background-color: #F00;
        position: absolute;
    }
</style>

<?php
Form::setData($data);
Form::setErrors($errors);
?>



<div class="main-tabs">
    <ul>
        <li><a href="#main-tabs-details">Details</a></li>
        <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
            <li><a href="#main-tabs-manipulate">Manipulate image</a></li>
            <li><a href="#main-tabs-focus">Set focal point</a></li>
        <?php endif; ?>
        <li><a href="#main-tabs-replace">Replace file</a></li>
        <li><a href="#main-tabs-cats">Categories</a></li>
    </ul>

    <div id="main-tabs-details">
        <?php Form::nextFieldDetails('Name', true); ?>
        <?= Form::text('name'); ?>

        <?php Form::nextFieldDetails('Description', false); ?>
        <?= Form::multiline('description', ['rows' => 5, 'cols' => 40]); ?>

        <?php Form::nextFieldDetails('Author', false); ?>
        <?= Form::autocomplete('author', [], ['url' => 'admin/call/file/ajaxAuthorLookup', 'save_id' => false]); ?>


        <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
            <?php
            Form::nextFieldDetails('Embed author credit in image', false);
            echo Form::multiradio('embed_author', [], [1 => 'Yes', 0 => 'No']);
            ?>

        <?php elseif ($data['type'] == FileConstants::TYPE_DOCUMENT): ?>
            <?php
            Form::nextFieldDetails('Document type', false);
            echo Form::dropdown('document_type', [], $document_types);
            ?>

            <?php
            Form::nextFieldDetails('Date published', false);
            echo Form::datepicker('date_published');
            ?>
        <?php endif; ?>


        <?php
        Form::nextFieldDetails('Filename', false);
        echo Form::out($data['filename']);
        ?>

        <?php
        $abs_url = Enc::html(File::absUrl($data['filename']));
        Form::nextFieldDetails('URL', false);
        echo Form::html('<a href="' . $abs_url . '" target="_blank">' . $abs_url . '</a>');
        ?>

        <?php
        Form::nextFieldDetails('Type', false);
        echo Form::out(FileConstants::$type_names[$data['type']]);
        ?>


        <!-- Preview -->
        <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
            <?php
            Form::nextFieldDetails('Image dimensions', false);
            echo Form::out($img_dimensions);
            ?>

            <h3>Preview</h3>
            <p>
                <a href="<?php echo Enc::html(File::absUrl($data['filename']));; ?>" target="_blank">
                <img src="<?php echo Enc::html(File::resizeUrl($data['filename'], 'r200x0')); ?>" alt="preview">
                </a>
            </p>

            <?php
            if (count($sizes)) {
                echo '<h3>Available sizes</h3>';

                echo '<table class="main-list">';
                echo '<thead><tr>';
                echo '<th>Filename</th><th>Size</th><th>Dimensions</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                foreach ($sizes as $filename) {
                    $abs_url = Enc::html(File::absUrl($filename));
                    $dimensions = File::imageSize($filename);
                    $size = File::size($filename);

                    echo '<tr>';
                    echo '<td><a href="', $abs_url, '" target="_blank">', Enc::html($filename), '</a></td>';
                    echo '<td>', File::humanSize($size), '</td>';
                    echo '<td>', $dimensions[0], 'x', $dimensions[1], '</td>';
                }
                echo '</tbody>';
                echo '</table>';
            }
            ?>

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
                    <select id="manipulate" name="manipulate">
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
                        <img src="<?php echo Enc::html(File::resizeUrl($data['filename'], 'r200x0')); ?>" alt="">
                    </div>

                    <div class="column column-6">
                        <p><b>New image:</b></p>
                        <img src="SITE/admin/call/file/previewTransform/none/<?php echo Enc::html($data['filename']); ?>" alt="" id="manipulate-preview">
                    </div>
                </div>

            </div>
        </div>

        <div id="main-tabs-focus">
            <?php Fb::heading('Set focal point'); ?>

            <p>Click the position on the image where you want the focal point to be set.</p>
            <p>When the image is resized, the resizing will be done so that the focal point is always visible, and as close to the centre as possible.</p>

            <div id="focal-point-wrapper"><img id="focal-point-setter" src="<?php echo File::url($data['filename']); ?>"><div id="focal-point-dot"></div></div>

            <input type="hidden" id="image-focal-point" name="focal_point" value="<?php echo enc::html(@$data['focal_point']); ?>">
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
