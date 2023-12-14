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
<form action="SITE/admin/login" method="get" class="login-form">
    <input type="hidden" name="redirect" value="<?php echo Enc::html($_GET['redirect'] ?? ''); ?>">
    <input type="hidden" name="nomsg" value="1">

    <?php echo $msg; ?>

    <p>&nbsp;</p>
    <p><input type="submit" value="Continue to login form &raquo;" class="button"></p>
</form>
