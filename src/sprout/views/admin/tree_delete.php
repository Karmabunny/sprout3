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
use Sprout\Helpers\Inflector;

if (count($children) == 0) {
    $plural = 'this ' . Inflector::singular($friendly_name);
} else {
    $plural = 'these ' . Inflector::plural(Inflector::singular($friendly_name));
}
?>

<p>You are about to delete <?= $plural; ?>:</p>

<ul style="margin: 10px 0;">
    <li><?= Enc::html($item['name']); ?></li>

    <?php foreach ($children as $child): ?>
        <li>
            <?= Enc::html($child['name']); ?>
        </li>
    <?php endforeach; ?>
</ul>


<div class="message-bar-warning">
    <p>Are you sure you want to delete <?= Enc::html($plural); ?>?</p>
    <p>Deleting is an irreversible action.</p>
</div>

