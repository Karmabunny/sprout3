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
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;

Needs::fileGroup('sprout/file_selector_popup');
Needs::fileGroup('drag_drop_upload');
echo Needs::dynamicNeedsLoader();
?>

<script type="text/javascript">
    $(document).ready(function(){
        initTabs();
    });
</script>

<div class="main-tabs">
    <?php if ($browse and $upload): ?>
        <ul>
            <li><a href="#tab-select-file">Select</a></li>
            <li><a href="#tab-upload-file">Upload</a></li>
        </ul>
    <?php endif; ?>

    <div class="tab" id="tab-select-file">

        <?php if ($browse): ?>

            <!-- Search existing -->
            <form action="SITE/admin/call/file/selectorPopupSearch" method="get" id="file-selector-search">
                <input type="hidden" name="f_type" value="<?php echo $f_type; ?>">
                <input type="hidden" name="page" value="0" id="file-selector-page">

                <h3 class="popup-subtitle">Search existing files</h3>

                <div class="field-elements-inline">
                    <?php
                    Form::nextFieldDetails('Category', false);
                    echo Form::dropdown('category_id', ['-dropdown-top' => 'All', '-wrapper-class' => 'white'], $cats);
                    ?>

                    <?php
                    Form::nextFieldDetails('Name', false);
                    echo Form::text('name', ['-wrapper-class' => 'white', 'placeholder' => 'Enter a name']);
                    ?>

                    <div class="field-element field-element--button">
                        <button type="submit" class="button icon-after icon-search button-green button-regular">Search</button>
                    </div>
                </div>

            </form>

        <?php endif; ?>

        <div class="file-selector-search-wrapper">
            <div id="file-selector-preview"><p class="preview-title">Preview image</p><div class="preview-box"></div></div>
            <div id="file-selector-stats"></div>
            <div id="file-selector-result-wrap">
                <div id="file-selector-results"></div>
            </div>
            <div id="file-selector-paginate" class="-clearfix"></div>
        </div>

    </div>
    <div class="tab" id="tab-upload-file">

        <?php if ($upload): ?>

            <h2 class="popup-title">Select a file</h2>
            <!-- Upload -->
            <form action="SITE/admin/call/file/quickUpload" method="post" target="quick-upload" id="file-selector-upload" data-type="<?php echo $f_type; ?>">
                <?= Csrf::token(); ?>

                <h3 class="popup-subtitle">Upload</h3>
                <?php
                Form::nextFieldDetails('File', true);
                echo Form::chunkedUpload('file', [], ['sess_key' => 'admin_quick_upload']);
                ?>

                <div class="field-elements-inline">

                    <?php
                    if ($cat_create) {
                        $cats = ['_new' => '- New category -'] + $cats;
                    }
                    Form::nextFieldDetails('Category', (bool) $req_category);
                    echo Form::dropdown('category_id', ['-dropdown-top' => 'Select a file category', '-wrapper-class' => 'white select-category'], $cats);
                    ?>

                    <?php
                    Form::nextFieldDetails('New file category', true);
                    echo Form::text('category_new', ['-wrapper-class' => 'white new-category', 'placeholder' => 'Enter a category name']);
                    ?>

                    <?php
                    Form::nextFieldDetails('Name', true);
                    echo Form::text('name', ['-wrapper-class' => 'white', 'placeholder' => 'Enter a name']);
                    ?>

                    <div class="field-element field-element--button">
                        <button type="submit" class="button icon-after icon-file_upload button-green button-regular">Upload</button>
                    </div>
                </div>

            </form>

            <iframe name="quick-upload" id="quick-upload" style="display: none;"></iframe>

        <?php endif; ?>

    </div>
</div>

<script>init_fileselector_popup();</script>
