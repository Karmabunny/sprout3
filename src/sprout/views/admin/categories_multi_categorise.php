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
use Sprout\Helpers\Admin;
use Sprout\Helpers\Form;



if (empty($data['mode'])) {
    $data['mode'] = 'add';
}
Form::setData($data);
?>


<form action="SITE/admin/call/<?php echo $controller_name; ?>/postMultiCategorise" method="post" id="main-form">
    <?= Csrf::token(); ?>
    <?php foreach ($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?php echo (int)$id; ?>">
    <?php endforeach; ?>


    <h3>Records to categorise</h3>
    <?php echo $itemlist; ?>


    <h3>Options</h3>
    <?php
    Admin::categorySelection('categories[]', $cats, []);
    ?>


    <?php
    Form::nextFieldDetails('Recategorisation mode', true);
    echo Form::multiradio('mode', [], [
        'add' => 'Add the specified categories',
        'mod' => 'Replace the existing categories with the specified categories',
    ]);
    ?>


    <div class="action-bar">
        <button type="submit" class="button button-regular button-green icon-after icon-save">Save changes</button>
    </div>
</form>


