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


if (@count($child_pages) == 0) {
    $a = 'this page';
    $b = 'Delete page';

} else {
    $count = count($child_pages) + 1;

    $a = "these {$count} pages";
    $b = "Delete {$count} pages";
}
?>



<p>You are about to delete <?= $a; ?>:</p>

<ul style="margin: 10px 0;">
    <li><?= Enc::html($page['name']); ?></li>

    <?php foreach ($child_pages as $child): ?>
        <li>
            <?= Enc::html($child['name']); ?>
        </li>
    <?php endforeach; ?>
</ul>

<p>Are you sure you want to do this?</p>


<p>&nbsp;</p>
<div class="message-bar-warning">
    <p>Are you sure you want to delete <?= $a; ?>?</p>
    <p>Deleting a page is an irreversible action.</p>
</div>

