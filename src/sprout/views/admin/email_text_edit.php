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
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Fb::setData($data);
?>


<?php Fb::heading('Replace fields'); ?>
<?php
echo '<table class="pretty-list">';
echo '<thead><tr><th>Name</th><th>Description</th></tr></thead>';
echo '<tbody>';
foreach ($field_defs as $name => $desc) {
    echo '<tr>';
    echo '<td><code>{{', Enc::html($name), '}}</code></td>';
    echo '<td>', Enc::html($desc), '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
?>


<?php Form::nextFieldDetails('Text', true); ?>
<?= Form::fieldAuto('Sprout\Helpers\Fb::richtext', 'text'); ?>

<?php Admin::clearFieldErrors(); ?>
