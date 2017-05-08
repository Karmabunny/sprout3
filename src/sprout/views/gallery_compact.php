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
use Sprout\Helpers\File;



?>

<?php foreach ($images as $image): ?>

    <div class="image-tall-compact">
        <a href="<?php echo Enc::html(File::url($image->popup_filename)); ?>" rel="facebox">
            <img src="<?php echo Enc::html(File::resizeUrl($image->filename, 'c50x50')); ?>" alt="" title="<?php echo Enc::html($image->name); ?>">
        </a>
    </div>

<?php endforeach; ?>

<div style="clear: both;"><!-- --></div>
