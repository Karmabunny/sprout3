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

use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Pdb;

Form::setData($data);
Form::setErrors($errors);
?>

<form method="post" action="<?= Enc::html($submit_url); ?>">

<?php
Form::nextFieldDetails('How did you hear about us?', false);
echo Form::dropdown('how_heard', [], Pdb::extractEnumArr('multistep_demo_submissions', 'how_heard'));
?>

<?php
Form::nextFieldDetails('Tell us why you love us', true);
echo Form::richtext('why_love');
?>

<input class="button" type="submit" value="Finish him!">

</form>