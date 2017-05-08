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


Form::setData(['mode' => 'cat']);
?>


<div class="message-bar-warning">
    <p>Are you sure you want to delete this category?</p>
    <p>Deleting a category is an irreversible action.</p>
</div>

<?php
Form::nextFieldDetails('Deletion mode', true);
echo Form::multiradio('mode', [], [
    'cat' => 'Delete the category only',
    'cont' => 'Delete the category and its contents (' . $num_in_cat . ($num_in_cat == 1 ? ' record' : ' records') . ')',
]);
?>
