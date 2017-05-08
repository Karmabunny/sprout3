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
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Needs;



Needs::module('sprout/admin_multiselect_tools');
$category_ctlr = $controller_name . '_category';
?>


<form action="" method="get" class="selection-action">
    <?= Csrf::token(); ?>

    <?php echo $itemlist; ?>


    <div class="selected-tools">
        <strong>Selected <?php echo strtolower($friendly_name); ?>:</strong>

        <ul class="inline-list inline-list-broken">
            <li>
                <a href="SITE/admin/call/<?php echo $controller_name; ?>/postJsonMultiTag" class="selection-action multiple-add-tag">Add tag</a>
            </li>
            <?php if (!empty($allow_del)): ?>
                <li>
                    <a href="SITE/admin/extra/<?php echo $controller_name; ?>/multi_delete" class="selection-action">Delete</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</form>

