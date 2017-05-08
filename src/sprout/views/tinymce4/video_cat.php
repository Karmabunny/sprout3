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
?>

<?= $toolbar; ?>

<div class="info">
    Choose a category of videos from below.
</div>

<ul class="link-list">
<?php foreach ($categories as $row): ?>
    <li class="container"><a href="SITE/tinymce4/video_list/<?= $row['id']; ?>"><?= Enc::html($row['name']); ?>
        (<?= Inflector::numPlural($row['num_files'], 'video'); ?>)</a>
    </li>
<?php endforeach; ?>
</ul>
