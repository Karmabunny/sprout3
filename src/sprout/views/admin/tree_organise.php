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
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;


Needs::module('jstree');
Needs::module('page_organise');

echo '<script>admin_auth = {is_remote: ', (AdminAuth::isSuper() ? 'true' : 'false'), '};</script>';
?>


<div id="jstree_demo_div" style="width: 1000px;">
    <ul>
    <?php
    // Basic recursive tree node renderer in the format which jsTree expects
    function node($nd) {
        echo '<li id="', $nd['id'], '">', $nd['name'];
        if (count($nd->children)) {
            echo '<ul>';
            foreach ($nd->children as $child) {
                node($child);
            }
            echo '</ul>';
        }
        echo '</li>';
    }

    // Render the top-level pages
    foreach ($root->children as $child) {
        node($child);
    }
    ?>
    </ul>
</div>


<div class="del"></div>


<form id="main-form" action="SITE/admin/call/<?= Enc::html($controller_name); ?>/organiseAction" method="post">
    <?php echo Csrf::token(); ?>

    <input type="hidden" name="data" value="" id="hidden-data">

    <div class="action-bar">
       <button type="submit" class="button button-regular button-green icon-after icon-save">Save changes</button>
    </div>
</form>
