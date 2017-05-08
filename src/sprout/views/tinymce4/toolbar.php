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
use Sprout\Helpers\Fb;
?>

<link href="ROOT/sprout/media/css/admin_layout.css" rel="stylesheet">

<div class="emu-mce-wrapper">
    <div class="emu-mce-toolbar">
        <?php if (!$is_root): ?>
        <div class="left">
            <a class="button button-small button-grey" href="javascript:history.go(-1);">Back</a>
        </div>
        <?php endif; ?>

        <?php if ($can_upload): ?>
        <div class="right">
            <a class="button button-small" href="SITE/<?php echo Enc::html($upload_url); ?>"><?php echo Enc::html($upload_label); ?></a>
        </div>
        <?php endif; ?>

        <form method="get" action="SITE/<?php echo Enc::html($search_url); ?>">
            <?php if (isset($search_params)) echo Fb::hiddenFields($search_params); ?>

            <label class="emu-mce-label" for="mceu_img_search_input"><?php echo Enc::html($search_label); ?></label>
            <div class="field-element field-element--text field-element--white" style="width: 170px; margin: 0 9px 0 0; vertical-align: top; display: inline-block;">
                <div class="field-input">
                    <input id="mceu_img_search_input" class="textbox" type="text" name="search" value="<?php echo Enc::html($search_query); ?>">
                </div>
            </div>

            <input class="button button-small" type="submit" value="Search" style="vertical-align: top;">

            <?php if ($is_search): ?>
            <a class="button button-grey button-small" href="SITE/<?php echo Enc::html($reset_url); ?>" style="vertical-align: top;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>
