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

use Sprout\Helpers\Form;
?>


<style>
.login-box {
    max-width: 900px;
    margin: 2em auto;
    padding-top: 0;
}
</style>


<form action="welcome/super_op_action" method="post">

    <?php
    Form::nextFieldDetails('Username', true, 'Must be a single word, only letters and numbers allowed');
    echo Form::text('username');
    ?>

    <div style="background: #eee; padding: 15px; margin: 40px 0 15px 0;">
        <b>Password complexity requirements:</b>
        <br>
        16 characters
        &nbsp; <i>OR</i> &nbsp;
        8 characters with at least 1 uppercase, 1 lowercase, and 1 number
    </div>

    <div class="clear-group">
        <div class="col col--one-half">
            <?php
            Form::nextFieldDetails('Enter new password', true);
            echo Form::password('password1', ['autocomplete' => 'off']);
            ?>
        </div>
        <div class="col col--one-half">
            <?php
            Form::nextFieldDetails('Enter it again', true);
            echo Form::password('password2', ['autocomplete' => 'off']);
            ?>
        </div>
    </div>


    <div>
        <a href="welcome/checklist" class="button button-grey">Back</a>
        <button type="submit" class="button right button-green icon-before icon-save">Generate config</button>
    </div>
</form>
