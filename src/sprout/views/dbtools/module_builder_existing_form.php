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
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Form::setData($data);
Form::setErrors($errors);
?>


<form action="SITE/dbtools/moduleBuilderExistingAction/<?php echo Enc::html($input_xml); ?>" method="post" enctype="multipart/form-data">

    <div class="mainbar-with-right-sidebar">
        <h3>Module settings</h3>
        <?php
        Form::nextFieldDetails('Module author', true, '(CamelCaps)');
        echo Form::text('module_author');

        Form::nextFieldDetails('Module name', true, '(CamelCaps)');
        echo Form::text('module_name');
        ?>

        <h3>Tables</h3>
        <table class="main-list">
            <thead>
            <tr>
                <th>Table</th>
                <th>Admin controller</th>
                <th>Controller name</th>
                <th>Single name</th>
                <th>Plural name</th>
                <th>Single label</th>
                <th>Plural label</th>
            </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $name => $defn): ?>
                    <tr>
                    <td><b><?php echo Enc::html($name); ?></b></td>
                    <td><?php echo Fb::dropdown("tables[{$name}]", [], $templates); ?></td>
                    <td><?php echo Fb::text("tables_cname[{$name}]"); ?></td>
                    <td><?php echo Fb::text("tables_sname[{$name}]"); ?></td>
                    <td><?php echo Enc::html($name); ?></td>
                    <td><?php echo Fb::text("tables_snice[{$name}]"); ?></td>
                    <td><?php echo Fb::text("tables_pnice[{$name}]"); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-keyboard_arrow_right">Create Module</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-file_download">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>
