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
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;


if (!Sprout::browserDragdropUploads() and empty($_GET['force'])) {
    echo '<p>Your browser doesn\'t support drag-and-drop file uploads.</p>';
    echo '<p>&nbsp;</p>';
    echo '<p>To enable this feature, you must use a supported browser:</p>';
    echo '<ul>';
    echo '<li>Firefox 4+';
    echo '<li>Internet Explorer 10+';
    echo '<li>Chrome 13+';
    echo '<li>Safari 6+';
    echo '</ul>';
    echo '<p>&nbsp;</p>';
    echo '<p><a href="admin/add/file?force=1">Ignore this warning and let me upload anyway</a></p>';
    return;
}

Needs::fileGroup('moment');
Needs::fileGroup('daterangepicker');
Needs::fileGroup('fb');
Needs::fileGroup('drag_drop_upload');
?>


<!-- Don't hide the "drop files here" message on this specific view.
It's a on-page important style because most other calls to drag_drop_upload.js do want the message hidden -->
<style>
.file-upload__helptext--hidden { display: block !important; }
</style>


<h3>Settings</h3>
<div class="drag-drop__form">

    <?php
    Form::nextFieldDetails('Category', false);
    echo Form::dropdown('category_id', ['-dropdown-top' => 'Choose per file'], Pdb::lookup('files_cat_list'));
    ?>

</div>


<div class="field-element field-element--chunkedupload field-element--chunkedupload--form">
    <div class="field-label"><label for="field5">Upload files</label></div>
    <div class="field-input">
        <div class="fb-chunked-upload" data-opts="<?= Enc::html(json_encode($opts)); ?>">
            <input class="file-upload__input upload" id="field5" type="file" name="uploader_upload" value="" multiple>
            <div class="file-upload__area textbox">
                <div class="file-upload__helptext">
                    <p>Drop file here <span class="file-upload__helptext__line2">or click to upload</span></p>
                </div>
                <div class="file-upload__uploads">
                    <div class="save_all" style="display: none;"><button type="button" class="button button-green">Save all</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
