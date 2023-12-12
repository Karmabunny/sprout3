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

use Sprout\Helpers\Form;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Pdb;


Form::setData($data);
Form::setErrors($errors);
?>

<h3 class="h2">Category name</h3>
<?= Form::text('name'); ?>


<h3 class="h2">Options</h3>
<div class="white-box">
    <?= Form::checkboxList([
        'access_reportemail' => 'Receives automatically-generated report emails',
    ]); ?>
</div>

<?php
Form::nextFieldDetails('Restrict access to specific IPs', false, 'Enter a comma-separated list of IP addresses or CIDR blocks that are allowed access.<br>Leave blank to have no restriction.');
echo Form::text('allowed_ips');
?>


<h3 class="h2">General Permissions</h3>
<div class="white-box">
    <?= Form::checkboxList([
        'access_operators' => 'Can edit operators, operator categories and subsites; can also use cms tools and view cms logs',
        'access_homepage' => 'Can edit the home page',
        'access_email_report' => 'Can create custom automated email reports',
        'access_noapproval' => 'Page edits do not require approval to be made live',
    ]); ?>
</div>


<h3 class="h2">Per-Tab Permissions</h3>
<div class="info">
    This allows for fine-graned control of the admin tabs which are available to operators in this category.
</div>

<?= Form::multiradio('default_allow', [], [
    '1' => 'By default allow all access, except as per below',
    '0' => 'By default don\'t allow access, except as per below',
]); ?>

<div id="multiedit-permissions">
    <input type="hidden" name="m_id">

    <div class="clear-group">
        <div class="col col--one-half">
            <?php
            Form::nextFieldDetails('For the tab', false);
            echo Form::dropdown('m_controller', [], $controllers);
            ?>
        </div>
        <div class="col col--one-half">
            <?php
            Form::nextFieldDetails('Allow the operator to', false);
            echo Form::checkboxList([
                'm_access_contents' => 'View the main list',
                'm_access_add' => 'Add records',
                'm_access_edit' => 'Edit records',
                'm_access_delete' => 'Delete records',
                'm_access_categories' => 'Manage categories',
                'm_access_import' => 'Import records',
                'm_access_export' => 'Export records',
                'm_access_report' => 'Generate reports',
                'm_access_email_report' => 'Automate custom email reports',
                'm_access_reorder' => 'Update record ordering',
            ]);
            ?>
        </div>
    </div>
</div>

<?php MultiEdit::display('permissions', $data['multiedit_permissions']); ?>


<h3 class="h2">Per-Subsite Permissions</h3>
<div class="white-box">
    <div class="info">
        Specify the subsites that operators of this category can manage content for.
    </div>

    <?= Form::multiradio('access_all_subsites', [], [
        '1' => 'By default allow all access, except as per below',
        '0' => 'By default don\'t allow access, except as per below',
    ]); ?>

    <?php Form::nextFieldDetails('Sub-site categories', false); ?>
    <?= Form::checkboxSet('subsites_permitted', [], $subsites); ?>
</div>


<h3 class="h2">Operator Management Permissions</h3>
<div class="white-box">
    <div class="info">
        Specify the categories of operators that these operators can manage.
        Management is limited to adding, editing and deleting operators.
        For full control, use the checkbox above under <i>General Permissions</i>.
    </div>
    <?php Form::nextFieldDetails('Categories', false); ?>
    <?= Form::checkboxSet('manage_categories', [], Pdb::lookup('operators_cat_list')); ?>
</div>
