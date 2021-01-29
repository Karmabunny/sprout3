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
<form action="SITE/search" method="get">

    <div class="field-element-attach-wrapper">
        <div class="field-element field-element--text field-element--hidden-label">
            <div class="field-label">
                <label for="fm-site-search">Search the <?php echo Enc::html(Kohana::config('sprout.site_title')); ?> website</label>
            </div>
            <div class="field-input">
                <input id="fm-site-search" class="textbox" type="text" name="q" value="<?php echo Enc::html(@$_GET['q']); ?>" placeholder="Enter your search here">
            </div>
        </div>
        <button type="submit" class="field-element-attach-wrapper__button">
            <span class="-vis-hidden">Search</span>
            <div class="bg-icon bg-icon--search"></div>
        </button>
    </div>



</form>
