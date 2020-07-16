<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
use Sprout\Helpers\Itemlist;
?>

<form action="admin/call/page/importUploadAction" method="post" enctype="multipart/form-data" class="-clearfix">
    <?php echo Csrf::token(); ?>

    <div class="mainbar-with-right-sidebar">

        <div class="field-element white-box">
            <div class="field-label">
                <label for="fb1">Select file <span class="field-label__required">required</span></label>
            </div>
            <div class="field-input">
                <input type="file" class="upload" name="import" id="fb1">
            </div>
        </div>

        <h3>Supported file types</h3>

        <?php
        if (empty($list)):
            echo '<div class="info highlight-warning">No document importers installed</div>';
        else:
            echo $list;
        endif;
        ?>

    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-file_upload">Import</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <?php if (!empty($list)): ?>
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-send">Upload file</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</form>
