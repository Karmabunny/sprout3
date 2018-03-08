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

use Sprout\Helpers\Form;
use Sprout\Helpers\Csrf;
?>


<form action="admin/call/my_settings/detailsAction" method="post">
    <?= Csrf::token(); ?>

    <?php
    Form::nextFieldDetails('Real name', true);
    echo Form::text('name');
    ?>

    <?php
    Form::nextFieldDetails('Email', true);
    echo Form::text('email');
    ?>


    <h3>Change Password</h3>

    <?php
    Form::nextFieldDetails('Current password', true);
    echo Form::password('old_password');
    ?>

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


    <div class="action-bar">
        <button type="submit" class="button button-green">Save</button>
    </div>
</form>
