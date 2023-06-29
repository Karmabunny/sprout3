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

<?php if (!empty($image_too_large)): ?>

    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">
        <p class="file-upload__item__feedback__error__text">This image is too large</p>
    </div>

    <?php return; ?>
<?php elseif (!empty($unsupported_image_type)): ?>

    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">
        <p class="file-upload__item__feedback__error__text">This image format is not supported</p>
    </div>

    <?php return; ?>
<?php elseif (!empty($error)): ?>

    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">
        <p class="file-upload__item__feedback__error__text"><?= Enc::html($error); ?></p>
    </div>

    <?php return; ?>
<?php endif; ?>


<?php if (!empty($shrunk_img)): ?>

    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--success file-upload__item__feedback__response--success--image">
        <div style="background-image: url(data:image/png;base64,<?= Enc::html($shrunk_img['encoded_thumbnail']); ?>)" class="file-upload__item__feedback__image"></div>

        <div class="file-upload__item__feedback__hover">
            <p class="file-upload__item__feedback__hover__name"><?php echo Enc::html($orig_file['name']); ?></p>
            <p class="file-upload__item__feedback__hover__size"><?php echo Enc::html(File::humanSize($orig_file['size'])); ?></p>
            <p class="file-upload__item__feedback__hover__dimensions"><?= Enc::html($shrunk_img['original_height']); ?> &times; <?= Enc::html($shrunk_img['original_width']); ?></p>
        </div>
    </div>

<?php else : ?>

    <div class="file-upload__item__feedback__response file-upload__item__feedback__response--success file-upload__item__feedback__response--success--not-image">

        <p class="file-upload__item__feedback__name"><?php echo Enc::html($orig_file['name']); ?></p>
        <p class="file-upload__item__feedback__size"><?php echo Enc::html(File::humanSize($orig_file['size'])); ?></p>

    </div>

<?php endif; ?>




