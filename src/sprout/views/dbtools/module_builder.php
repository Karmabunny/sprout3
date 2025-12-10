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

if (!$temp_writeable) {
    echo "<ul class=\"messages\"><li class=\"error\">Temp dir not writeable :(</li></ul>\n";
    return;
}


$templates = array(
    'has_categories' => 'Categories',
    'tree' => 'Tree',
    'list' => 'Sprout List',
    'simple_list' => 'Simple List',
);

$data = array(
    'module_author' => 'Karmabunny',
    'module_name' => 'FruityFruits',
    'module_type' => 'has_categories',
    'cname' => 'Fruit',
    'pname' => 'fruits',
    'sname' => 'fruit',
    'snice' => 'Fruit',
    'pnice' => 'Fruits',
    'fields' => "colour\njuiciness\nprice",
);
Fb::setData($data);
?>


<script type="text/javascript">
$(document).ready(function() {
    $('th span.s:has(ul)').css('margin-top', '10px');
    var change_handler = function() {
        var val = $('input[name=cname]').val();
        if (val == '') return;

        var sname = val.substr(0, 1).toLowerCase() + val.substr(1).replace(/([A-Z])/g, '_$1').toLowerCase();
        var pname = sname.substr(val.length - 1) == 's'? sname + 'es': sname + 's';
        var snice = sname.replace('_', ' ');
        snice = snice.substr(0, 1).toUpperCase() + snice.substr(1);
        var pnice = snice.substr(snice.length - 1) == 's'? snice + 'es': snice + 's';

        $('input[name=sname]').val(sname);
        $('input[name=pname]').val(pname);
        $('input[name=snice]').val(snice);
        $('input[name=pnice]').val(pnice);
    };
    $('input[name=cname]').keyup(change_handler);
    $('input[name=cname]').change(change_handler);
    $('input[name=cname]').blur(change_handler);

    $('#module-choice').change(function() {
        $('input[name="module_name"]').val($(this).val());
    });
});
</script>





<form action="SITE/dbtools/moduleBuilderAction" method="post">

    <div class="mainbar-with-right-sidebar">

        <?php echo Form::heading('Module'); ?>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-third">
                <?php Form::nextFieldDetails('Prefill', false, 'Available modules'); ?>
                <?php echo Form::dropdown('module-choice', ['id' => 'module-choice', '-wrapper-class' => 'white'], $modules); ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php Form::nextFieldDetails('Module author', true, 'CamelCaps'); ?>
                <?php echo Form::text('module_author', ['-wrapper-class' => 'white']); ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php Form::nextFieldDetails('Module name', true, 'CamelCaps'); ?>
                <?php echo Form::text('module_name', ['-wrapper-class' => 'white']); ?>
            </div>
        </div>

        <?php echo Form::heading('Controller'); ?>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Template', true, 'Controller type'); ?>
                <?php echo Form::dropdown('module_type', ['-wrapper-class' => 'white'], $templates); ?>
            </div>
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Controller name', true, 'Singular'); ?>
                <?php echo Form::text('cname', ['-wrapper-class' => 'white']); ?>
            </div>

        </div>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Single name', true, 'Subtable name'); ?>
                <?php echo Form::text('sname', ['-wrapper-class' => 'white']); ?>
            </div>

            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Single label', true, 'Human name'); ?>
                <?php echo Form::text('snice', ['-wrapper-class' => 'white']); ?>
            </div>
        </div>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Plural name', true, 'Table name'); ?>
                <?php echo Form::text('pname', ['-wrapper-class' => 'white']); ?>
            </div>

            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Plural label', true, 'Human name'); ?>
                <?php echo Form::text('pnice', ['-wrapper-class' => 'white']); ?>
            </div>
        </div>

        <?php echo Form::heading('Fields'); ?>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Fields', false, 'One per line'); ?>
                <?php echo Form::multiline('fields', ['-wrapper-class' => 'white', 'rows' => 13]); ?>
            </div>

            <div class="field-group-item col col--one-half">
                <h4>Don't include these fields:</h4>
                <ul>
                    <?php foreach($bad_fields as $bf): ?>
                        <li><?php echo Enc::html($bf); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-keyboard_arrow_right">Create Module</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-file_download">Create module</button>
                </div>
            </div>
        </div>
    </div>
</form>
