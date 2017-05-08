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
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;


Form::setData(['import_type' => 'none']);

Needs::module('underscore');
Needs::module('sprout/cms_page_import_options');
?>



<form action="SITE/admin/import_action/page" method="post" id="main-form">
    <?php echo Csrf::token(); ?>
    <input type="hidden" name="timestamp" value="<?php echo (int)$_GET['timestamp']; ?>">
    <input type="hidden" name="ext" value="<?php echo Enc::html($_GET['ext']); ?>">


    <?php
    Form::nextFieldDetails('Import type', true);
    echo Form::multiradio('import_type', [], [
        'none' => 'No splitting; import the whole document as a single page',
        'heading' => 'Use headings',
    ]);
    ?>

    <?php
    Form::nextFieldDetails('Parent page', true);
    echo Form::pageDropdown('parent_id');
    ?>


    <!-- Single page -->
    <div class="import-type" data-type="none">
        <?php
        Form::nextFieldDetails('Page name', true);
        echo Form::text('page_name');
        ?>
    </div>

    <!-- Headings -->
    <div class="import-type" data-type="heading">
        <?php
        Form::nextFieldDetails('Top-level page name', true);
        echo Form::text('top_page_name');
        ?>

        <?php
        Form::nextFieldDetails('Create pages for', true);
        echo Form::pageDropdown('heading_level', [], [
            1 => 'Level 1 headings',
            2 => 'Level 2 headings and above',
            3 => 'Level 3 headings and above',
        ]);
        ?>
    </div>

    <div class="preview"></div>


    <div class="action-bar">
        <button type="submit" class="button">Import document</button>
    </div>
</form>
