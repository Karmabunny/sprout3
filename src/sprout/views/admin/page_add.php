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
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\NavigationGroups;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Register;
use Sprout\Helpers\Subsites;


Needs::fileGroup('sprout/page_edit');

Form::setData($data);
Form::setErrors($errors);
?>


<h3>Page details</h3>

<input type="hidden" name="type" value="<?= Enc::html($data['type']); ?>">

<?php
Form::nextFieldDetails('Name', true, 'This name will appear as the primary heading of this page, and will also be used in your navigation and sitemap');
echo Form::text('name', ['-wrapper-class' => 'white', 'spellcheck' => 'true']);
?>

<?php
Form::nextFieldDetails('Parent page', false, 'The location in your navigation where this page will be displayed');
echo Form::pageDropdown('parent_id', ['-wrapper-class' => 'white']);
?>

<?php if ($data['type'] == 'tool'): ?>
    <?php Form::nextFieldDetails('Module', true); ?>
    <?= Form::dropdown('controller_entrance', ['-wrapper-class' => 'white'], $front_end_controllers); ?>

    <?php Form::nextFieldDetails('Option', true); ?>
    <?= Form::dropdown('controller_argument', ['-wrapper-class' => 'white'], $controller_arguments); ?>
<?php endif; ?>

<?php if (Subsites::getConfigAdmin('nav_groups') !== null): ?>
    <?php
    Form::nextFieldDetails('Menu group', false, 'The group to place this page into in the main navigation menu');
    echo Form::dropdown('menu_group', ['-dropdown-top' => 'None -- Top level page', '-wrapper-class' => 'white'], NavigationGroups::getAllNamesAdmin());
    ?>
<?php endif; ?>

<?php
Form::nextFieldDetails('Search engine description', false, 'The description to show in search results from search engines like Google. Treat it as an advertisement for this page');
echo Form::text('meta_description', ['-wrapper-class' => 'white', 'spellcheck' => 'true']);
?>


<?php if (Register::hasFeature('users')): ?>
    <h3>Who can view this page?</h3>
    <div class="info">You can restrict which user groups can access this page.</div>
    <?php
    echo Form::multiradio('user_perm_specific', [], [
        0 => 'Anyone who can view the parent page',
        1 => 'Choose specific user groups',
    ]);
    ?>
    <div class="user_perms">
        <?php
        echo Form::checkboxSet('user_permissions[]', [], $user_category_options);
        ?>
    </div>
<?php endif; ?>


<h3>Who can manage this page?</h3>
<div class="info">You can restrict which operator groups can edit this page.</div>
<?php
echo Form::multiradio('admin_perm_specific', [], [
    0 => 'Anyone who can manage the parent page',
    1 => 'Choose specific operator groups',
]);
?>
<div class="admin_perms">
    <?php
    echo Form::checkboxSet('admin_permissions[]', [], $admin_category_options);
    ?>
</div>


<?php if ($data['type'] != 'tool'): ?>
    <br>
    <div class="info highlight-confirm">You will be able to enter your page content in the next step.</div>
<?php endif; ?>


<?php Admin::clearFieldErrors(); ?>
