<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;


Needs::fileGroup('jstree');
Needs::fileGroup('page_organise');

echo '<script>admin_auth = {is_remote: ', (AdminAuth::isSuper() ? 'true' : 'false'), '};</script>';
?>

<form id="main-form" action="SITE/admin/call/<?= Enc::html($controller_name); ?>/organiseAction" method="post" class="-clearfix">
    <?php echo Csrf::token(); ?>

    <input type="hidden" name="data" value="" id="hidden-data">

    <div class="mainbar-with-right-sidebar">

        <div id="jstree_demo_div" class="white-box">
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

    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-open_with">Organise</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-save">Save changes</button>
                </div>
            </div>
        </div>
    </div>

</form>
