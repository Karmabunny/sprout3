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

use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;


if (!empty($_GET['username'])) {
    $autofocus = 'p';
} else {
    $autofocus = 'u';
}
?>


<form action="SITE/admin/login_action" method="post" class="login-form" autocomplete="off">
    <?= Csrf::token(); ?>

    <input type="hidden" name="redirect" value="<?php echo Enc::html($_GET['redirect'] ?? ''); ?>">

    <div class="field-element field-element--text field-element--required">
        <div class="field-label -vis-hidden">
            <label for="field0">Username <span class="field-label__required">required</span></label>
        </div>
        <div class="field-input">
            <input id="field0" class="textbox" type="text" name="Username" <?php if ($autofocus == 'u') echo 'autofocus'; ?> value="<?= Enc::html($_GET['username'] ?? ''); ?>" placeholder="Username">
        </div>
    </div>

    <div class="field-element field-element--text field-element--required">
        <div class="field-label -vis-hidden">
            <label for="field1">Password <span class="field-label__required">required</span></label>
        </div>
        <div class="field-input">
            <input id="field1" class="textbox" type="password" name="Password" <?php if ($autofocus == 'p') echo 'autofocus'; ?> placeholder="Password">
        </div>
    </div>

    <div class="text-align-right">
        <button type="submit" class="login-button button button-regular button-green">Log in</button>
    </div>
</form>


