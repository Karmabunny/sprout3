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
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Form::setData([
    'format' => 'csv',
]);

echo $refine;
?>

<h3>Preview of records</h3>
<div class="scrollable-table"><?php echo $itemlist; ?></div>

<form action="SITE/admin/ai_reprocess_action/<?php echo Enc::html($controller_name); ?>" method="post">
    <?php echo Csrf::token(); ?>
    <?= Fb::hiddenFields($refine_fields); ?>

    <?php echo $ai_view; ?>

    <div class="action-bar">
        <button type="submit" class="button button-green button-regular icon-after icon-keyboard_arrow_right no-disable">Generate content</button>
    </div>
</form>
