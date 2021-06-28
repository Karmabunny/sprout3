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
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\AdminAuth;


Needs::fileGroup('sprout/admin_page_navigation');
?>

<div class="inline-buttons sidebar-action-buttons -clearfix">
    <a class="icon-after icon-add tree-list-add button button-small" href="SITE/admin/add/<?php echo $controller_name; ?>">Add <?php echo Inflector::singular($friendly_name); ?></a>
    <?php if (AdminAuth::isSuper()): ?>
        <a class="icon-after icon-add tree-list-add button button-small" href="SITE/admin/add/<?php echo $controller_name; ?>?type=tool">Add tool <?php echo Inflector::singular($friendly_name); ?></a>
    <?php endif; ?>
</div>

<ul class="tree-list">
    <?php
    $class = (Admin::getControllerSlug() === 'home_page' ? 'active-node' : '');
    ?>

    <li class="node depth1 allow-access <?= $class; ?>" data-id="0">
        <div>
            <a class="node-link" href="SITE/admin/edit/home_page/<?= (int) $home_page_id; ?>">Home</a>
            <button class="tree-list-settings-button icon-before icon-settings" type="button">Settings</button>
            <div class="tree-list-settings-dropdown dropdown-box">
                <ul class="tree-list-settings-dropdown-list list-style-2">
                    <li class="tree-list-settings-dropdown-list-item">
                        <a href="SITE/admin/edit/home_page/<?= (int) $home_page_id; ?>">Edit home page</a>
                    </li>
                </ul>
            </div>
        </div>
    </li>

    <?php
    $nav_limit = Subsites::getConfigAdmin('nav_limit');
    if (! $nav_limit) $nav_limit = 99999;
    if (Subsites::getConfigAdmin('nav_home')) $nav_limit--;

    $dropdown_actions = [
        [
            'name' => 'Edit page',
            'url' => 'admin/edit/page/%%',
        ],
        [
            'name' => 'Add child',
            'url' => 'admin/add/page?parent_id=%%',
        ],
        [
            'name' => 'Reorder children',
            'url' => 'admin/call/page/reorder/%%',
            'class' => 'popup',
        ],
        [
            'name' => 'Delete page',
            'url' => 'admin/delete/page/%%',
        ],
    ];

    $dropdown_actions_no_children = [
        [
            'name' => 'Edit page',
            'url' => 'admin/edit/page/%%',
        ],
        [
            'name' => 'Add child',
            'url' => 'admin/add/page?parent_id=%%',
        ],
        [
            'name' => 'Delete page',
            'url' => 'admin/delete/page/%%',
        ],
    ];

    foreach ($root->children as $node) {
        if ($nav_limit == 0) {
            echo '</ul>';
            echo '<p class="page-tree-list-over-nav-limit">Pages not in navigation</p>';
            echo '<ul class="tree-list">';
        }
            if (count($node->children) > 0) {
                Admin::navigationTreeNode($node, $dropdown_actions);
            } else {
                Admin::navigationTreeNode($node, $dropdown_actions_no_children);
            }

            $nav_limit--;
       }
    ?>
</ul>



