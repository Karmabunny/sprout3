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

use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Url;


$types = array();
foreach ($_GET['type'] as $t) {
    $types[] = $avail_types[$t];
}
$cols = [];
foreach (['q', 'tag', 'date'] as $field) {
    if (!empty($_GET[$field])) {
        $cols[] = $field;
    }
}
$grid_class = 'grid-col';
if ($cols) $grid_class .= ' grid-col-' . 12 / count($cols);
?>

<p><?= Enc::html(implode(', ', $types)); ?></p>

<div class="grid grid-<?= count($cols); ?>-cols">
    <?php if (!empty($_GET['q'])): ?>
        <div class="<?= $grid_class; ?>">
            <?= Form::nextFieldDetails('Keywords', false); ?>
            <?= Form::html(Enc::html($_GET['q']) . '<br>' . Constants::$search_modifiers[$_GET['q_type']]); ?>
        </div>
    <?php endif; ?>


    <?php if (!empty($_GET['tag'])): ?>
        <div class="<?= $grid_class; ?>">
            <?= Form::nextFieldDetails('Tags', false); ?>
            <?= Form::html(Enc::html(trim($_GET['tag'], ', ')) . '<br>' . Constants::$search_modifiers[$_GET['tag_type']]); ?>
        </div>
    <?php endif; ?>


    <?php if (!empty($_GET['date'])): ?>
        <div class="<?= $grid_class; ?>">
            <?= Form::nextFieldDetails('Last modified', false); ?>
            <?= Form::html(Enc::html(Constants::$relative_dates[$_GET['date']])); ?>
        </div>
    <?php endif; ?>
</div>

<p class="buttons">
    <a href="<?php echo Url::withoutArgs('page') . 'fullform=1'; ?>">
        Update search options &raquo;
    </a>
</p>
