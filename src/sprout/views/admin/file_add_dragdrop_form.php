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
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;


Form::setData($data);
Form::setErrors($errors);
?>


<?php if (!empty($image_too_large)): ?>
    <script type="text/javascript">
    $(document).ready(function(){
        $(".file-upload__item__feedback__response--error").closest('.file-upload__item').addClass("file-upload__item--completed");
    });
    </script>
    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">
        <p class="file-upload__item__feedback__error__text">This image is too large</p>
        <p class="file-upload__item__feedback__error__text">
            You may wish to use
            <a target="_blank" href="http://www.karmabunny.com.au/our_work/open_source/resiz_o_tron">Resize-o-Tron</a>
            to shrink this image to a suitable size.
        </p>
    </div>
    <?php return; ?>
<?php endif; ?>


<form action="admin/call/file/ajaxDragdropSave" method="post">
    <?= Csrf::token(); ?>
    <input type="hidden" name="tmp_file" value="<?php echo Enc::html($tmp_file); ?>">
    <input type="hidden" name="orig_name" value="<?php echo Enc::html($orig_file['name']); ?>">

    <div class="columns">
        <div class="column column-10">
            <!-- Form fields column -->

            <div class="-clearfix">
                <div class="col col--one-half">
                    <?php
                    Form::nextFieldDetails('Name', true);
                    echo Form::text('name');
                    ?>
                </div>

                <div class="col col--one-half">
                    <?php
                    Form::nextFieldDetails('Category', true);
                    echo Form::dropdown('category_id', [], $categories);
                    ?>
                </div>
            </div>

            <div class="-clearfix">
                <?php if ($data['type'] == FileConstants::TYPE_DOCUMENT): ?>
                    <div class="col col--one-half">
                        <?php
                        Form::nextFieldDetails('Date published', false);
                        echo Form::datepicker('date_published');
                        ?>
                    </div>
                <?php endif; ?>

                <div class="col col--one-half">
                    <?php
                    Form::nextFieldDetails('Author', false);
                    echo Form::autocomplete('author', [], ['url' => 'admin/call/file/ajaxAuthorLookup', 'save_id' => false]);
                    ?>
                </div>

                <?php if ($data['type'] == FileConstants::TYPE_DOCUMENT and !empty($document_types)): ?>
                    <div class="col col--one-half">
                        <?php
                        Form::nextFieldDetails('Document type', false);
                        echo Form::dropdown('document_type', [], $document_types);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($data['type'] == FileConstants::TYPE_IMAGE): ?>
                    <div class="col col--one-half">
                        <?php
                        Form::nextFieldDetails('Embed author credit in image', false);
                        echo Form::dropdown('embed_author', [], [1 => 'Yes', 0 => 'No']);
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($data['type'] == FileConstants::TYPE_IMAGE and !empty($shrink_original)): ?>
                <?php
                Form::nextFieldDetails('This image is very large. Is it a specifically cropped banner?', false);
                echo Form::dropdown('shrink_original', [], [
                    1 => 'No, this is a normal photo or other image',
                    0 => 'Yes, this is a banner image, cropped to specific dimensions',
                ]);
                ?>
            <?php endif; ?>

            <p>
                <button type="submit" class="button button-green icon-after icon-save">Save</button>
                <button class="file-upload__item__remove" type="button"><span class="file-upload__item__remove__text">Remove</span></button>
            </p>

            <!-- END form fields column -->
        </div>
        <div class="column column-2">
            <!-- Preview column -->

            <div class="field-element">
                <div class="field-label"><?php echo Enc::html(FileConstants::$type_names[$data['type']]); ?></div>
                <div class="field-input">
                    <?php
                    if (!empty($shrunk_img)) {
                        echo Enc::html($shrunk_img['original_width']), ' &times; ', Enc::html($shrunk_img['original_height']);
                    } else {
                        echo File::humanSize($size_bytes);
                    }
                    ?>
                </div>
            </div>

            <?php
            if (!empty($shrunk_img)) {
                echo '<img class="file-upload__preview-img" src="data:image/png;base64,', Enc::html($shrunk_img['encoded_thumbnail']), '">';
            }
            ?>

            <?php if ($data['type'] == FileConstants::TYPE_SOUND): ?>
                <audio class="file-upload__preview-sound" src="admin/call/file/downloadTemp/<?= Enc::html($tmp_file); ?>"
                    style="width: 100%;" controls
                ></audio>
            <?php endif; ?>

            <?php if ($data['type'] == FileConstants::TYPE_VIDEO): ?>
                <video class="file-upload__preview-video" src="admin/call/file/downloadTemp/<?= Enc::html($tmp_file); ?>"
                    style="width: 100%;" controls
                ></video>
            <?php endif; ?>

            <!-- END preview column -->
        </div>
    </div>

    <script type="text/javascript">
        $(document).find('input[name="tmp_file"][value="<?= Enc::html($tmp_file); ?>"]').closest('.file-upload__item').find('.file-upload__item__remove').first().remove();
    </script>
</form>
