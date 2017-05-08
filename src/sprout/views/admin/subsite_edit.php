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

use Sprout\Helpers\Admin;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Form::setData($data);
Form::setErrors($errors);
?>


<?php
Form::nextFieldDetails('Name', false);
echo Form::text('name', ['spellcheck' => 'true']);
?>

<?php
Form::nextFieldDetails('Skin code', false);
echo Form::dropdown('code', [], $codes);
?>


<?= Fb::heading('Options'); ?>
<?php Admin::checkboxList(null, array(
    'mobile' => 'Mobile site',
    'require_admin' => 'Only allow access if logged-in to admin area',
    'require_user' => 'Only allow access if logged-in to user module',
), $data); ?>


<?php if (@count($subsites) > 0): ?>
    <?php
    Form::nextFieldDetails('Shared content', false);
    echo Form::dropdown('content_id', [], $subsites);
    ?>
<?php endif; ?>


<h3>Display conditions</h3>

<?php
Form::nextFieldDetails('Domain is one of', false);
echo Form::multiline('cond_domain', ['style' => 'height: 75px;']);
?>

<?php
Form::nextFieldDetails('Directory matches', false);
echo Form::text('cond_directory');
?>


<?php Admin::clearFieldErrors(); ?>
