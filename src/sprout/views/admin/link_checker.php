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
use Sprout\Helpers\Form;


Form::setData(['send_to' => 'admins', 'email' => $email]);
?>


<script>
$(document).ready(function() {
    $('#main-form input[name=send_to]').change(function() {
        $('#main-form .group').hide();
        $('#main-form .group-' + $(this).val()).show();
    });
});
</script>


<form action="SITE/admin/call/page/linkCheckerAction" method="post" id="main-form">
    <?= Csrf::token(); ?>

    <?php
    Form::nextFieldDetails('Send to', true);
    echo Form::multiradio('send_to', [], [
        'admins' => 'All administrators',
        'specific' => 'A specific email address'
    ]);
    ?>

    <div class="group group-admins">
        <?php
        if (empty($ops)) {
            echo '<div class="info">There are no users specified to receive email reports. ';
            echo 'Configure users in the <a href="admin/intro/operator">operators</a> section of the admin.</div>';

        } else {
            echo '<ul>';
            foreach ($ops as $row) {
                if (!$row['email']) continue;
                echo '<li>', Enc::html($row['name']), ' (', Enc::html($row['email']), ')</li>';
            }
            echo '</ul>';
        }
        ?>
    </div>

    <div class="group group-specific" style="display: none;">
        <?php Form::nextFieldDetails('Email address', true); ?>
        <?= Form::text('email'); ?>
    </div>


    <div class="action-bar">
        <button type="submit" name="submit" class="button icon-after icon-send">Start link checker</button>
    </div>
</form>
