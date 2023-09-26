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
use Sprout\Helpers\Form;


Form::setData($data);
Form::setErrors($errors);
?>


<form action="SITE/dbtools/moduleBuilderExistingModelAction/<?php echo Enc::html($input_xml); ?>" method="post" enctype="multipart/form-data">

    <h3>Model settings</h3>

    <?php
    Form::nextFieldDetails('Model name', true, 'E.g Entity (CamelCaps)');
    echo Form::text('model_name');
    ?>

    <?php
    Form::nextFieldDetails('Namespace', true, 'E.g Sprout\Models');
    echo Form::text('namespace');
    ?>

    <div class="mainbar-with-right-sidebar">

        <h3>Tables</h3>
        <table class="main-list">
            <thead>
            <tr>
                <th>Table</th>
            </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $name => $defn): ?>
                    <tr>
                    <td><?php echo Form::multiradio('table', [], [$name => $name]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-keyboard_arrow_right">Create Model</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-file_download no-disable">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>
