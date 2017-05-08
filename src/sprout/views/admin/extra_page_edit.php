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
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Register;


Form::setData($data);
Form::setErrors($errors);
?>


<?php if (AdminAuth::isSuper()): ?>
    <h3>Type</h3>
    <?= Form::dropdown('type', [], Register::getExtraPages()); ?>

<?php else: ?>
    <input type="hidden" name="type" value="<?php echo Enc::html($data['type']); ?>">

<?php endif; ?>

<h3>Text</h3>
<?= Form::richtext('text'); ?>


<?php Admin::clearFieldErrors(); ?>
