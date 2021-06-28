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


Needs::fileGroup('moment');
Needs::fileGroup('daterangepicker');
Needs::fileGroup('fb');
Needs::fileGroup('drag_drop_upload');
?>


<div class="fb-multiple-file-select"
    data-name="<?php echo Enc::html($name); ?>"
    data-opts="<?php echo Enc::html(json_encode($opts)); ?>">

    <div class="drag-drop__upload file-upload__area textbox">
        <div class="file-upload__helptext">
            <p>Drag-and-Drop files here to upload them.</p>
            <p><span class="file-upload__helptext__line2">or <a href="javascript:;" data-filter="<?= Enc::html($filter); ?>" class="select-existing-file">select an existing file</a></span></p>
        </div>

        <div class="file-upload__uploads">
            <?php
            foreach ($data as $id) {
                $filename = isset($filenames[$id]) ? $filenames[$id] : $id;
                echo '<div class="file-upload__item file-upload__item--existing">';
                echo '<input type="hidden" name="', Enc::html($name), '" value="', Enc::html($id), '">';
                echo '<img class="file-upload__item__feedback__existing-image" src="', Enc::html(File::resizeUrl($filename, 'm200x133')), '" alt="">';
                echo '<p class="file-upload__item__feedback__name">', Enc::html($filename), '</p>';
                echo '<p class="file-upload__item__feedback__size">', File::humanSize(File::size($filename)), '</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>
