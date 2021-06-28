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
use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\NavigationGroups;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Register;
use Sprout\Helpers\Request;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Text;
use Sprout\Helpers\WidgetArea;


if ($data['type'] == 'tool' and empty($front_end_controllers)) {
    echo '<p><i>No tool page types have been defined. Unable to edit tool pages.</i></p>';
    return;
}

Needs::fileGroup('sprout/page_edit');
Needs::fileGroup('sprout/underscore');
Needs::fileGroup('drag_drop_upload');

if ($show_tour) {
    Needs::fileGroup('sprout/backbone');
    Needs::fileGroup('sprout/tourist');
    Needs::fileGroup('sprout/tourist-page-edit');
}


if ($data['alt_template'] == '') $data['alt_template'] = 'skin/inner';

$subsite_absroot = Subsites::getAbsRoot($_SESSION['admin']['active_subsite'], Request::protocol());

$base = Subsites::getAbsRoot($_SESSION['admin']['active_subsite']);
$root = Navigation::getRootNode();
$node = $root->findNodeValue('id', $id);

if ($node) {
    $share_type = 'Public URL';
    $share_url = $base . $node->getFriendlyUrlNoprefix();
} else {
    $share_type = 'Temporary URL';
    $share_url = $base . 'page/view_by_id/' . $id;
}
?>

<?php
Form::setData($data);
Form::setErrors($errors);
?>


<input type="hidden" name="rev_id" value="<?= $sel_rev_id; ?>">
<input type="hidden" name="type" value="<?= Enc::html($data['type']); ?>">

<!-- Settings -->
<div id="page-settings-wrapper" class="page-edit-tab">
    <div class="heading-with-buttons">
        <button class="button button-small button-grey icon-close icon-after page-edit-tab-close" type="button" data-target="page-settings-wrapper">Close</button>
        <h3 class="h2 icon-before icon-settings">Settings</h3>
    </div>
    <div class="white-box">
        <?php
        Form::nextFieldDetails('Template', true, 'Overrides the default page container template; e.g. utilise a wide page rather than a column view.');
        echo Form::dropdown('alt_template', [], $templates);
        ?>

        <?php if (Subsites::getConfigAdmin('nav_groups') !== null): ?>
            <?php
            Form::nextFieldDetails('Menu group', false);
            echo Form::dropdown('menu_group', ['-dropdown-top' => 'None -- Don\'t show in menu'], NavigationGroups::getAllNamesAdmin());
            ?>
        <?php endif; ?>

        <?php if (Kohana::config('page.enable_banners')): ?>
            <?php
            Form::nextFieldDetails('Banner', false, 'Overrides the default banner image when the page is viewed.');
            echo Form::fileSelector('banner', [], ['filter' => FileConstants::TYPE_IMAGE]);
            ?>
        <?php endif; ?>

        <?php
        Form::nextFieldDetails('Gallery Thumbnail', false, 'A thumbnail for the page when seen in the page gallery.');
        echo Form::fileSelector('gallery_thumb', [], ['filter' => FileConstants::TYPE_IMAGE]);
        ?>


        <h3>Old content</h3>

        <?php
        Form::nextFieldDetails('Auto deactivate', false, 'Automatically hide the page from users after this date.');
        echo Form::datepicker('date_expire', []);
        ?>

        <?php
        if ($data['type'] == 'standard') {
            $help_text = "A warning about stale content is sent if this page hasn't been updated for this many days";
            $help_text .= " (enter 0 to disable such warnings)";
            Form::nextFieldDetails('Maximum days before content is stale', false, $help_text);
            echo Form::text(
                'stale_age',
                [
                    'placeholder' => Kohana::config('sprout.stale_page_age'),
                ]
            );
        }
        ?>


        <h3>Metadata</h3>

        <?php
        Form::nextFieldDetails('URL slug', true, 'The text used to generate a link for the page; e.g. "this-is-a-slug" would result in: ' . Sprout::absRoot() . 'this-is-a-slug');
        echo Form::text('slug', []);
        ?>

        <?php
        Form::nextFieldDetails('Keywords', false, 'Terms that relate to the content on the page; these are important for search relevancy.');
        echo Form::text('meta_keywords', ['size' => 30]);
        ?>

        <?php
        Form::nextFieldDetails('Description', false, 'A short (under 100 words) summary of the page content.');
        echo Form::text('meta_description', ['size' => 30]);
        ?>

        <?php
        if (empty($page['alt_browser_title'])) {
            Form::nextFieldDetails('Web-browser title (defaults to: ' . Navigation::buildBrowserTitle($page['name']) . ')',
                false,
                'Specify a custom browser title for the page; this will appear in the browser title bar/tab.'
            );
        } else {
            Form::nextFieldDetails('Web-browser title', false, 'A custom browser title for the page; this will appear in the browser title bar/tab.');
        }
        echo Form::text('alt_browser_title', ['size' => 30]);
        ?>

        <?php
        Form::nextFieldDetails('Alternate navigation title', false, 'Override the default title that appears in links and navigation breadcrumbs for this page.');
        echo Form::text('alt_nav_title', ['size' => 30]);
        ?>

        <?php echo Fb::heading('Custom attributes'); ?>
        <div class="info">You can use these to fulfill any operational need you may have.</div>
        <?php Admin::attrEditor($data['multiedit_attrs']); ?>


        <!-- Permissions -->
        <div>
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
                    echo Form::checkboxSet('user_permissions', [], $user_category_options);
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
                echo Form::checkboxSet('admin_permissions', [], $admin_category_options);
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Revisions tab -->
<div id="page-revisions-wrapper" class="page-edit-tab">
    <div class="heading-with-buttons">
        <button class="button button-small button-grey icon-close icon-after page-edit-tab-close" type="button" data-target="page-revisions-wrapper">Close</button>
        <h3 class="h2 icon-before icon-history">Page Revisions</h3>
    </div>
    <div class="white-box">
        <div class="info">This is a list of all revisions which have been made to this page.</div>
        <table class="main-list main-list--small main-list-no-js" id="rev-list">
            <thead>
                <tr>
                    <th style="width: 20px;">&nbsp;</th>
                    <th style="width: 165px;">Date modified</th>
                    <th>Changes</th>
                    <th style="width: 200px">Editor</th>
                    <th style="width: 200px">Status</th>
                    <th style="width: 200px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($revs as $rev) {
                $rev_edit_url = "admin/edit/page/{$id}?revision={$rev['id']}";
                $rev_view_url = "page/view_specific_rev/{$id}/{$rev['id']}";

                if ($can_approve_revisions and $rev['status'] == 'need_approval') {
                    $rev_view_url .= '/' . $rev['approval_code'];
                }
                ?>

                <tr>
                    <td>
                        <?php if ($sel_rev_id == $rev['id']): ?>
                            <span class="icon-before icon-edit" title="This revision is currently being edited"></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo Enc::html($rev['date_modified']); ?></td>
                    <td><?php echo Enc::html($rev['changes_made']); ?></td>
                    <td><?php echo Enc::html($rev['modified_editor']); ?></td>
                    <td>
                        <?php
                        echo Enc::html(Constants::$rev_statuses[$rev['status']]);
                        if ($rev['status'] == 'auto_launch') {
                            echo ' on ', date('d/m/Y', strtotime($rev['date_launch']));
                        }
                        ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <a href="<?= Enc::html($rev_edit_url); ?>" class="button button-small button-green icon-before icon-edit">Edit</a>
                        <a href="<?= Enc::html($rev_view_url); ?>" class="button button-small button-blue icon-before icon-remove_red_eye">View</a>
                    </td>
                </tr>

                <?php
                }
            ?>
            </tbody>
        </table>


        <?php if (count($revs) != count($history)): ?>
            <?php echo Fb::heading('Page history'); ?>
            <div class="info">This is a history of changes to this page.</div>
            <table class="main-list main-list--small">
            <thead>
            <tr>
                <th style="width: 195px">Date</th>
                <th>Changes</th>
                <th style="width: 200px">Editor</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?php echo Enc::html($item['date_added']); ?></td>
                    <td><?php echo Enc::html($item['changes_made']); ?></td>
                    <td><?php echo Enc::html($item['modified_editor']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Main content editing -->
<div class="content-bar">

    <?php
    if ($admin_notes) {
        echo '<ul class="messages all-type-neutral"><li class="neutral">', Text::richtext($admin_notes), '</li></ul>';
    }
    ?>

    <?php if (!$has_live_rev and empty($_GET['suppress'])): ?>
        <ul class="messages all-type-neutral"><li class="neutral">NOTE: modify the 'Publish options' to put this page live.</li></ul>
    <?php elseif ($page['active'] != 1): ?>
        <ul class="messages all-type-neutral"><li class="neutral">This page is not currently active on the website.</li></ul>
    <?php endif; ?>

    <div class="columns -clearfix">
        <div class="column column-7">
            <div id="tour-page-title">
                <?php Form::nextFieldDetails('Name', true); ?>
                <?= Form::text('name', ['spellcheck' => 'true', '-wrapper-class' => 'white']); ?>
            </div>
        </div>

        <div class="column column-5">
            <div id="tour-page-parent">
                <?php
                Form::nextFieldDetails('Parent page', false);
                echo Form::pageDropdown('parent_id', ['-wrapper-class' => 'white']);
                ?>
            </div>
        </div>
    </div>

    <?php if ($data['type'] == 'tool'): ?>
        <?php Form::nextFieldDetails('Module', true, 'The type of tool page to show'); ?>
        <?php echo Form::dropdown('controller_entrance', [], $front_end_controllers); ?>

        <?php Form::nextFieldDetails('Feature', true, 'The specific feature of the module to show'); ?>
        <?php echo Form::dropdown('controller_argument', [], $controller_arguments); ?>
    <?php elseif ($data['type'] == 'redirect'): ?>
        <?php echo Fb::heading('Redirect'); ?>
        <div class="info">If this is set, users will be redirected to another page on the website or an external location.</div>
        <?php echo Fb::lnk('redirect', [], ['-wrapper-class' => 'white']); ?>
    <?php else: ?>
        <div id="tour-content-area">
            <div class="heading-with-buttons">
                <?php if (empty($page['redirect'])): ?>
                    <button class="button button-small button-grey icon-keyboard_arrow_up icon-after content-block-collapse-button" type="button" data-target="wl-embedded">Collapse all</button>
                <?php endif; ?>
                <h3 class="h2 icon-before icon-content">Content</h3>
            </div>

            <p>Content Blocks build the structure of your page. Add, remove, disable, or reorder them how you wish.</p>

            <?php
            // Content blocks
            $areas = Kohana::config('sprout.widget_areas');
            foreach ($areas as $area_id => $area) {
                if ($area->getName() != 'embedded') continue;
                Admin::widgetList($area->getName(), $area, @$widgets[$area_id], empty($page['redirect']));
                break;
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($data['type'] != 'redirect'): ?>
<!-- Widgets-->
<div class="sidebar-widgets">
    <?php
    $areas = Kohana::config('sprout.widget_areas');
    foreach ($areas as $area_id => $area) {
        if ($area->getName() == 'embedded') continue;
        if ($area->getOrientation() == WidgetArea::ORIENTATION_EMAIL) continue;

        if ($area_id == 2) {
            $heading_class = 'h2 icon-before icon-sidebar';
        } else {
            $heading_class = 'h2';
        }

        echo '<div class="heading-with-buttons">';
        if (empty($page['redirect'])) {
            echo '<button class="button button-small button-grey icon-keyboard_arrow_up icon-after content-block-collapse-button" type="button" data-target="wl-sidebar">Collapse all</button>';
        }
        echo '<h3 class="', $heading_class, '">', Enc::html($area->getNiceName()), '</h3>';
        echo '</div>';

        if ($area_id == 2) {
            echo '<p>These content blocks will be displayed in the sidebar of the page.</p>';
        }

        Admin::widgetList($area->getName(), $area, @$widgets[$area_id], empty($page['redirect']));
    }
    ?>
</div>
<?php endif; ?>

<?php Admin::clearFieldErrors(); ?>
