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
use Sprout\Helpers\Subsites;
?>
<p>Hi</p>

<p>The following bad links were found on your website:</p>

<?php foreach ($errs as $idx => $links): ?>
    <?php list($id, $subsite_id, $name) = explode(':', $idx); ?>


    <h3><?php echo Enc::html($name); ?></h3>

    <p>
        <a href="<?php echo Subsites::getAbsRoot($subsite_id) . 'page/view_by_id/' . $id; ?>">View Page</a>
        &bull;
        <a href="<?php echo Subsites::getAbsRoot($subsite_id) . 'admin/edit/page/' . $id; ?>">Edit page</a>
    </p>


    <table class="main-list" width="100%">
    <thead>
        <tr>
            <th align="left" width="34%">Link text</th>
            <th align="left" width="33%">URL</th>
            <th align="left" width="33%">Error</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($links as $row): ?>
            <tr>
                <td align="left"><?php echo Enc::html($row['link_text']); ?></td>
                <td align="left"><?php echo Enc::html($row['link_href']); ?></td>
                <td align="left"><?php echo Enc::html($row['err']); ?></td>
            </tr>
        <?php endforeach; ?>

    </tbody>
    </table>

    <p>&nbsp;</p>

<?php endforeach; ?>

