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
?>

<form action="SITE/admin/call/<?php echo $controller_name; ?>/postMultiDelete" method="post" id="main-form">
    <?= Csrf::token(); ?>

    <?php foreach ($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?php echo (int)$id; ?>">
    <?php endforeach; ?>

    <div class="message-bar-warning">
        <p>Are you sure you want to delete these records?</p>
        <p>This is an irreversible action</p>
    </div>

    <h3><?php echo count($ids); ?> record<?php
    if(count($ids) > 1) {
        echo 's are';
    } else {
        echo ' is';
    }
    ?>
    selected for deletion</h3>

    <button type="submit" class="button button-red button-large icon-after icon-delete">Delete records</button>
</form>


