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

use Sprout\Helpers\Captcha;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;
use Sprout\Helpers\Spam;
?>
<p><b>You are sharing the following page:</b>
<br><a href="<?php echo Enc::html($data['url']); ?>"><?php echo Enc::html($data['title'] ? $data['title'] : $data['url']); ?></a></p>

<?php Form::setData($data); ?>
<?php Form::setErrors($errors); ?>
<form action="SITE/email_share/submit" method="post">
    <?= Csrf::token(); ?>
    <?= Spam::glue(); ?>

    <input type="hidden" name="title" value="<?php echo Enc::html($data['title']); ?>">
    <input type="hidden" name="url" value="<?php echo Enc::html($data['url']); ?>">

    <?php Form::nextFieldDetails('Their name', false); ?>
    <?= Form::text('their_name'); ?>

    <?php Form::nextFieldDetails('Their email', false); ?>
    <?= Form::text('their_email'); ?>

    <?php Form::nextFieldDetails('A short message', false); ?>
    <?= Form::multiline('message'); ?>

    <?php if (!empty($use_captcha)): ?>
        <?php Captcha::field(); ?>
    <?php endif; ?>

    <p>
        <input type="submit" value="Share" class="button">
        &nbsp;
        <a href="<?php echo Enc::html($data['url']); ?>">Cancel</a>
    </p>
</form>
