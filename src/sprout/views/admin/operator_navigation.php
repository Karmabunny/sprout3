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

use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Inflector;




$actions = array();
$actions['admin/add/' . $controller_name . '?category_id=%%'] = 'Add ' . Inflector::singular($friendly_name);

if (AdminPerms::canAccess('access_operators')) {
    $actions['admin/edit/' . $controller_name . '_category/%%'] = 'Edit category options';
    $actions['admin/delete/' . $controller_name . '_category/%%'] = 'Delete category';
}
?>


<script type="text/javascript">
$(document).ready(function () {
    var actions = <?php echo json_encode($actions); ?>;

    // Category actions box
    $('li.category a').mouseup(function(event) {
        if (event.button != 2) return false;

        var cat_id = $(this).attr('rel');

        var html = '<h3>Category actions</h3>';
        $.each(actions, function(url, label) {
            html += '<p><a href="' + url.replace('%%', cat_id) + '">' + label + '</a></p>';
        });

        show_foldout(html, this);

        event.stopPropagation();
        return false;
    });

    $('li.category a').each(function(i) {
        this.oncontextmenu = function() {return false;};
    });
});
</script>


<div class="inline-buttons sidebar-action-buttons -clearfix">
    <?php if (AdminPerms::canAccess('access_operators')): ?>
        <a class="icon-after icon-add button button-small" href="admin/add/<?php echo $controller_name; ?>">Add <?php echo Inflector::singular($friendly_name); ?></a>
    <?php endif; ?>
    <a class="icon-after icon-add button button-small" href="admin/add/<?php echo $controller_name . '_category'; ?>">Add category</a>
</div>

<ul class="tree-list">

    <li class="node depth1 all">
        <div>
            <a class="node-link" href="admin/contents/<?php echo $controller_name; ?>">All <?php echo strtolower($friendly_name); ?></a>
        </div>
    </li>

    <?php
    foreach ($categories as $id => $name) {
        $name = Enc::html($name);
        $class = (@$_GET['_category_id'] == $id ? 'category current-edit' : 'category'); ?>

        <li class="node depth1 <?php echo $class; ?>">
            <div>

                <a class="node-link" href="admin/contents/<?php echo $controller_name; ?>?_category_id=<?php echo $id; ?>"><?php echo $name; ?></a>

                <button class="tree-list-settings-button icon-before icon-settings" type="button">Settings</button>
                <div class="tree-list-settings-dropdown dropdown-box">
                    <ul class="tree-list-settings-dropdown-list list-style-2">
                        <li class="tree-list-settings-dropdown-list-item">
                            <a href="#">Add <?php echo Inflector::singular($friendly_name); ?></a>
                        </li>
                        <li class="tree-list-settings-dropdown-list-item">
                            <a href="#">Delete category</a>
                        </li>
                    </ul>
                </div>

            </div>
        </li>

    <?php } ?>

</ul>
