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


?>
<p>Hi,</p>

<p>The following pages have stale content, and should be updated:</p>

<table>
<tr>
    <th>Name</th>
    <th>Links</th>
    <th>Age (days)</th>
    <?php if ($show_op): ?>
        <th>Last edited by</th>
    <?php endif; ?>
</tr>

<?php foreach ($pages as $page): ?>
<tr>
    <td><?= Enc::html($page['name']); ?></td>
    <td><a href="<?= Enc::html($page['url']); ?>">view</a> |
        <a href="<?= Enc::html($base); ?>admin/edit/page/<?= $page['id']; ?>">edit</a>
    </td>
    <td><?= $page['age']; ?></td>
    <?php if ($show_op): ?>
        <td><?= Enc::html($page['editor']); ?></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
