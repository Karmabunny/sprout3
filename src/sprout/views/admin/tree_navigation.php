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
use Sprout\Helpers\Inflector;

// Inflector only works with single words, so only apply to last word
$words = explode(' ', $friendly_name);
$words[count($words)-1] = Inflector::singular($words[count($words)-1]);
?>

<script type="text/javascript">
function nav_actions(elem) {
    var item_id = $(elem).attr('rel').replace(/\/$/, '').replace(/^.*[\/\\]/g, '');

    var html = '<h3>Actions</h3>';
    html += '<p><a href="admin/add/<?php echo Enc::html($controller_name); ?>?parent_id=' + item_id + '">Add child</a></p>';
    html += '<p><a href="<?php echo Enc::html($controller_name); ?>/reorder/' + item_id + '" onclick="$.facebox({\"ajax\":this.href}); return false;">Re-order children</a></p>';
    html += '<p><a href="admin/delete/<?php echo Enc::html($controller_name); ?>/' + item_id + '">Delete</a></p>';

    show_foldout(html, elem);
}


// Show the tree
$(document).ready(function () {

    $('#nav').fileTree({
        root: '/',
        script: 'admin/call/<?= Enc::html($controller_name); ?>/filetreeOpen',
        closeScript: 'admin/call/<?= Enc::html($controller_name); ?>/filetreeClose',
        showNodes: [<?php echo $nodes_string; ?>],
        expandSpeed: 0,
        collapseSpeed: 0
    },
    function(action, file) {
        var item_id = file.replace(/^.*[\/\\]/g, '');
        window.location = 'SITE/admin/edit/<?php echo Enc::html($controller_name); ?>/' + item_id;
    });



});
</script>

<ul class="list-style-1">
    <li class="add"><a href="SITE/admin/add/<?php echo Enc::html($controller_name); ?>">Add <?php echo Enc::html(implode(' ', $words)); ?></a></li>
</ul>
<div id="nav">
&nbsp;
</div>
