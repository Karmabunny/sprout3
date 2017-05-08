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

use Sprout\Helpers\Enc;
?>


<style>
.login-box {
    max-width: 1200px;
    margin-bottom: 200px;
}
td {
    padding: 4px 8px;
    font-size: 12px;
    font-family: sans-serif;
}
</style>


<div class="center">
<table width="600" cellpadding="3" border="0">
    <tr class="h"><td colspan="2"><b>Basics</b></td></tr>
    <?php
    foreach ($vars as $key => $val) {
        echo '<tr><td>', $key, '</td><td>', Enc::html($val), '</td></tr>';
    }
    ?>
</table>
</div>

<p>&nbsp;</p>

<?php
phpinfo();
?>
