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
use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\Pdb;
?>


<style>span.mono { font-family: monospace; background: #eee; }</style>
<div class="info">
    <p>Use this tool to change the headings for the navigation menu.</p>
    <p>Notes:</p>
    <ul>
        <li>The sections below represent the top-level pages on your website which have a multi-column menu</li>
        <li>Each heading in a section must have a unique name</li>
        <li>Use a leading dash to hide the heading; e.g. <span class="mono">-One</span></li>
    </ul>
</div>


<form action="admin/call/page/menuGroupsAction" method="post">
    <?= Csrf::token(); ?>

    <?php
    foreach ($all_groups as $page_id => $groups) {
        try {
            $page = Pdb::get('pages', $page_id);
        } catch (RowMissingException $ex) {
            echo '<h3 style="color: red;">Page ' . (int)$page_id . ' does not exist</h3>';
            continue;
        }
        echo '<h3>', Enc::html($page['name']), '</h3>';


        if (array_sum($enabled_extras)) {
            Form::setData(array('extras' => $all_extras));

            if (!empty($enabled_extras['text'])) {
                Form::nextFieldDetails('Description', false);
                echo Form::multiline("extras[{$page_id}][text]", ['rows' => 3, 'cols' => 30], []);
            }

            if (!empty($enabled_extras['image'])) {
                Form::nextFieldDetails('Image', false);
                echo Form::fileselector("extras[{$page_id}][image]", [], ['type' => FileConstants::TYPE_IMAGE]);
            }
        }


        Form::setData(['groups' => $groups]);

        $index = 1;
        foreach ($groups as $id => $name) {
            Form::nextFieldDetails('Group ' . $index, false);
            echo Form::text("groups[{$id}][name]");
            $index++;
        }
    }
    ?>

    <div class="submit-bar">
        <button type="submit" class="button">Save changes</button>
    </div>
</form>


