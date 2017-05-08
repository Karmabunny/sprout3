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
<p>Hi <?php echo Enc::html($name); ?>,</p>

<p>There have been some updates on the <?php echo Enc::html($subsite_title); ?> website:</p>


<div class="content-subscribe-list">

    <?php foreach ($items as $row): ?>

        <div class="content-subscribe-item">
            <h2><?php echo Enc::html($row['name']); ?></h2>

            <p><?php echo Enc::html($row['text']); ?></p>

            <p><a href="<?php echo Enc::html($row['url']); ?>"><?php echo Enc::html($row['url']); ?></a></p>
        </div>

    <?php endforeach; ?>

</div>


<p>To unsubscribe from these notifications, use this link:
<br><a href="<?php echo $unsubscribe_url; ?>"><?php echo $unsubscribe_url; ?></a></p>
