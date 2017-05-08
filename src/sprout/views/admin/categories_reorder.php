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

use Sprout\Helpers\Enc;
use Sprout\Helpers\Csrf;


if (empty($action)) {
    $action = 'admin/call/' . $controller_name . '/reorderSave';
    if (isset($id)) $action .= '/' . $id;
}
?>


<!-- Sortable list -->
<script type="text/javascript">
$(document).ready(function() {
    $("#page-reorder .tree-list").sortable({
        placeholder: 'ui-state-highlight'
    });
    $("#page-reorder").disableSelection();
});
</script>


<form action="SITE/<?php echo $action; ?>" method="post" id="main-form">
    <?= Csrf::token(); ?>

    <p>Drag the records around below to re-order them:</p>

    <div id="page-reorder">

        <ul class="tree-list tree-list-grey">
            <?php foreach ($items as $item): ?>
            <li class="node depth1">
                <span>
                    <span class="node-link"><?php echo Enc::html($item['name']); ?></span>
                    <input type="hidden" name="items[]" value="<?php echo $item['id']; ?>">
                </span>
            </li>
            <?php endforeach; ?>
        </ul>

    </div>

    <div class="action-bar">
        <button type="submit" class="button button-regular button-green icon-after icon-save">Save changes</button>
    </div>
</form>
