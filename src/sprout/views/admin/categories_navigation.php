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

use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;
use Sprout\Helpers\Inflector;

?>
<div class="inline-buttons sidebar-action-buttons -clearfix">
    <a class="icon-after icon-add button button-small" href="admin/add/<?php echo $controller_name; ?>">Add <?php echo Enc::html(Inflector::singular($friendly_name)); ?></a>
    <a class="icon-after icon-add button button-small" href="admin/add/<?php echo $controller_name . '_category'; ?>" rel="facebox">Add category</a>
</div>

<ul class="tree-list">
    <?php
    $class = (@$_GET['_category_id'] === null ? 'all active-node' : 'all');
    ?>

    <li class="node depth1 <?php echo $class; ?>">
        <div>
            <a class="node-link" href="admin/contents/<?php echo $controller_name; ?>">All <?php echo Enc::html(strtolower($friendly_name)); ?></a>
        </div>
    </li>


    <?php foreach ($categories as $cat): ?>
        <?php
        $name = Enc::html($cat['name']);
        $class = (@$_GET['_category_id'] === $cat['id'] ? 'category active-node' : 'category');
        ?>

        <li class="node depth1 <?php echo $class; ?>">
            <div>
                <a class="node-link" href="admin/contents/<?php echo $controller_name; ?>?_category_id=<?php echo $cat['id']; ?>" rel="<?php echo $cat['id']; ?>"><?php echo $name; ?> <span class="tree-list-count"><?php echo $cat['num_items']; ?></span></a>

                <button class="tree-list-settings-button icon-before icon-settings" type="button">Settings</button>
                <div class="tree-list-settings-dropdown dropdown-box">
                    <ul class="tree-list-settings-dropdown-list list-style-2">
                        <li class="tree-list-settings-dropdown-list-item">
                            <a href="admin/contents/<?php echo $controller_name; ?>?_category_id=<?php echo $cat['id']; ?>">View <?php echo Inflector::plural('item', $cat['num_items']); ?></a>
                        </li>
                        <?php if ($category_archive and $cat['id']): ?>
                            <?php if ($cat['show_admin']): ?>
                                <li class="tree-list-settings-dropdown-list-item js--ajax-archive">
                                    <a href="admin/call/<?php echo $controller_name; ?>_category/ajaxArchiveAction/<?php echo $cat['id']; ?>">Archive Category</a>
                                </li>
                            <?php else: ?>
                                <li class="tree-list-settings-dropdown-list-item js--ajax-archive">
                                    <a href="admin/call/<?php echo $controller_name; ?>_category/ajaxUnarchiveAction/<?php echo $cat['id']; ?>">Unarchive Category</a>
                                </li>
                            <?php endif;?>
                        <?php endif; ?>
                        <li class="tree-list-settings-dropdown-list-item">
                            <a href="admin/add/<?php echo $controller_name; ?>?category_id=<?php echo $cat['id']; ?>">Add item</a>
                        </li>
                        <?php if ($cat['id']): ?>
                            <li class="tree-list-settings-dropdown-list-item">
                                <a href="admin/edit/<?php echo $controller_name; ?>_category/<?php echo $cat['id']; ?>">Edit</a>
                            </li>
                            <?php if ($cat['num_items'] > 1): ?>
                                <li class="tree-list-settings-dropdown-list-item">
                                    <a href="admin/extra/<?php echo $controller_name; ?>_category/reorder/<?php echo $cat['id']; ?>">Reorder</a>
                                </li>
                            <?php endif; ?>
                            <li class="tree-list-settings-dropdown-list-item">
                                <a href="admin/delete/<?php echo $controller_name; ?>_category/<?php echo $cat['id']; ?>">Delete category</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
<?php if (isset($category_archive_type)): ?>
    <form class="sidebar-form category-archive-selection" method="get">
        <?php Fb::$data['category_type'] = $category_archive_type; ?>
        <?php Form::nextFieldDetails('Showing Categories', false); ?>
        <?php echo Form::dropdown('category_type', ['-wrapper-class' => 'small white', 'placeholder' => 'Archive', 'title' => 'Select whether to show live categories, archived or everything.'], Constants::$category_admin_options); ?>
    </form>
    <script type="text/javascript">
    $(function () {
        $('.category-archive-selection select[name="category_type"]').change(function () {
            $(this).closest('form').submit();
        });
    });
    </script>
<?php endif; ?>
