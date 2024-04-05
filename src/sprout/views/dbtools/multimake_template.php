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

use Sprout\Helpers\Fb;
use Sprout\Helpers\MultiEdit;


die("Don't run me!");

// VIEW
?>

<?= Fb::heading('People'); ?>
<div id="multiedit-people">
    <input type="hidden" name="m_id">

    //INPUTS//

</div>

//REORDER//
<?php MultiEdit::itemName('Person'); ?>
<?php MultiEdit::display('people', $data['multiedit_people']); ?>

<?php
// CONTROLLER

# _editPreRender
if (!isset($view->data['multiedit_people'])) {
    $view->data['multiedit_people'] = MultiEdit::load('user_people', ['user_id' => $item_id], 'record_order');
}


# _addSave and _editSave
if (!is_array($_POST['multiedit_people'] ?? null)) {
    $_POST['multiedit_people'] = [];
}

//INIT_ORDER//
$new_set = [];
foreach ($_POST['multiedit_people'] as $idx => $data) {
    if (MultiEdit::recordEmpty($data)) continue;

    $update_fields = [];
    $update_fields['id'] = (int) $data['id'];
    //UPDATES//

    $new_set[] = $update_fields;
}
$this->replaceSet('user_people', $new_set, ['user_id' => $item_id]);

# _deleteSave method

$this->deleteRecord('user_people', $id);


