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
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Needs;


Needs::fileGroup('sprout/admin_contents_thumbs');
Needs::fileGroup('sprout/admin_multiselect_tools');
$category_ctlr = $controller_name . '_category';
?>


<form action="" method="get" class="selection-action">
    <?= Csrf::token(); ?>

    <div class="file-thumbs -clearfix">

        <?php
        foreach ($items as $row) {
            echo '<div class="thumb">';

                echo '<a class="image-link" href="SITE/admin/edit/file/', $row['id'], '" title="', Enc::html($row['name']), '">';
                if ($row['type'] == FileConstants::TYPE_IMAGE) {
                    echo '<img src="', File::resizeUrl($row['filename'], 'c180x180'), '" class="img">';
                } else {

                    if($row['type'] == FileConstants::TYPE_DOCUMENT) {
                        $placeholderIcon = 'icon-insert_drive_file';
                    } elseif($row['type'] == FileConstants::TYPE_SOUND) {
                        $placeholderIcon = 'icon-volume_up';
                    } elseif($row['type'] == FileConstants::TYPE_VIDEO) {
                        $placeholderIcon = 'icon-videocam';
                    } elseif($row['type'] == FileConstants::TYPE_OTHER) {
                        $placeholderIcon = 'icon-help';
                    } else {
                        $placeholderIcon = '';
                    }
                    echo '<div class="image-link__placeholder"><div class="image-link__placeholder__content"><div class="image-link__placeholder__icon icon-before ' . $placeholderIcon . '"></div>', FileConstants::$type_names[$row['type']], '</div></div>';
                }
                echo '</a>';

                echo '<div class="file-info">';
                    echo '<div class="name">';
                        echo '<a href="SITE/admin/edit/file/', $row['id'], '" title="', Enc::html($row['name']), '">', Enc::html($row['name']), '</a>';
                    echo '</div>';
                    echo '<div class="selection">';
                        echo '<div class="field-element field-element--white field-element--checkbox">';
                            echo '<div class="field-element__input-set">';
                                echo '<div class="fieldset-input">';
                                    echo '<input type="checkbox" id="file-list-', $row['id'], '" name="ids[]" value="', $row['id'], '">';
                                    echo '<label for="file-list-', $row['id'], '"><span class="-vis-hidden">Select file</span></label>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="delete"><a title="Delete" class="icon-before icon-close" href="SITE/admin/delete/file/', $row['id'], '"><span class="-vis-hidden">Delete</span></a></div>';
                echo '</div>';

            echo '</div>';
        }
        ?>

    </div>

    <script type="text/javascript">

    $(document).ready(function(){
        $(".file-thumbs .thumb").matchHeight();
    });

    </script>


    <div class="selected-tools">
        <strong>Selected <?php echo strtolower($friendly_name); ?>:</strong>

        <ul class="inline-list inline-list-broken">
           <li>
                <a href="SITE/admin/extra/<?php echo $controller_name; ?>/multi_categorise" class="selection-action">Categorise</a>
           </li>
            <li>
                <a href="SITE/admin/call/<?php echo $controller_name; ?>/postJsonMultiTag" class="selection-action multiple-add-tag">Add tag</a>
            </li>
            <li>
                <a href="SITE/admin/extra/<?php echo $controller_name; ?>/multi_delete" class="selection-action">Delete</a>
            </li>
        </ul>
    </div>

    <?php if (isset($category)): ?>
        <p>
            <b><i><?php echo Enc::html($category['name']); ?></i> category:</b>
            <a href="SITE/admin/extra/<?php echo $category_ctlr; ?>/reorder/<?php echo $category['id']; ?>">Reorder items</a>
            &bull;
            <a href="SITE/admin/edit/<?php echo $category_ctlr; ?>/<?php echo $category['id']; ?>">Edit</a>
            &bull;
            <a href="SITE/admin/delete/<?php echo $category_ctlr; ?>/<?php echo $category['id']; ?>">Delete</a>
        </p>
    <?php endif; ?>
</form>

