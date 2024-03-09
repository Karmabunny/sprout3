<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
?>


<style>
.login-box {
    max-width: 900px;
    margin: 2em auto;
    padding-top: 0;
}
.update-log .query { color: blue; border: none; padding: 0 0 0 100px; }
.update-log b { display: inline-block; width: 100px; }
.update-log p.heading { margin: 20px 0 5px; }
.update-log pre { margin-bottom: 0; }
</style>


<p><a href="welcome/checklist" class="button">Back to checklist</a></p>


<div class="update-log">
    <?php echo $log; ?>
</div>
