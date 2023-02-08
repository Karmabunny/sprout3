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
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Router;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Subsites;


$merged_js = 'media/merged/admin.' . Sprout::getVersion() . '.js';
$merged_css = 'media/merged/admin.' . Sprout::getVersion() . '.css';

$body_classes = array();
if (!empty($locked)) {
    $body_classes[] = 'record-locked';
}
if (!$nav and !$nav_tools) {
    $body_classes[] = 'no-sidebar';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title><?php echo $browser_title; ?> | SproutCMS</title>

    <base href="<?php echo Sprout::absRoot(); ?>">

    <script>var ROOT = 'ROOT/'; var SITE = 'SITE/';</script>

    <?php
    // Provide lock details to JavaScript to allow for unlocking on page unload
    if (!empty($currlock)) {
        echo '<script>var currlock = ', json_encode($currlock), ';</script>', "\n";
    }
    ?>

    <?php
    // Allow AJAX to use the CSRF token
    echo '<script>var csrfToken = "', Csrf::getTokenValue(), '";</script>', "\n";
    ?>

    <link rel="icon" href="ROOT/media/images/favicon.ico" type="image/x-icon" sizes="16x16 32x32 48x48 256x256">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-96x96.png" sizes="96x96">
    <link rel="apple-touch-icon" sizes="152x152" href="ROOT/media/images/apple-touch-icon-152x152.png">

    <!-- Styles -->
    <?php if (file_exists(DOCROOT . $merged_css)): ?>
    <link href="ROOT/<?php echo $merged_css; ?>" rel="stylesheet" type="text/css">
    <?php else: ?>
    <link href="ROOT/media/css/normalize.css" rel="stylesheet" type="text/css">
    <link href="ROOT/media/css/common.css" rel="stylesheet" type="text/css">
    <link href="ROOT/media/css/ui.core.css" rel="stylesheet" type="text/css">
    <link href="ROOT/sprout/media/css/admin_layout.css" rel="stylesheet">
    <link href="ROOT/sprout/media/css/admin_editing_area.css" rel="stylesheet">
    <link href="ROOT/media/css/facebox.css" rel="stylesheet">
    <?php endif; ?>

    <!-- jQuery + jQuery UI -->
    <?= Jquery::script('jquery', 'admin'); ?>
    <?= Jquery::script('jqueryui', 'admin'); ?>

    <!-- Libraries -->
    <?php if (file_exists(DOCROOT . $merged_js)): ?>
    <script src="ROOT/<?php echo $merged_js; ?>"></script>
    <?php else: ?>
    <script src="ROOT/media/js/jquery.cookie.js"></script>
    <script src="ROOT/media/js/common.js"></script>
    <script src="ROOT/media/js/jquery.matchHeight-min.js"></script>
    <script src="ROOT/sprout/media/js/admin_layout.js"></script>
    <script src="ROOT/sprout/media/js/admin_editing_area.js"></script>
    <script src="ROOT/media/js/facebox.js"></script>
    <?php endif; ?>

    <needs />

</head>
<body class="<?php echo implode(' ', $body_classes); ?>">

    <div id="wrapper">

        <div id="top-bar" class="-clearfix">
            <div class="container">
                <ul id="top-bar-nav" class="-clearfix">
                    <?php if (AdminAuth::isSuper()): ?>
                        <li class="top-bar-nav-item">
                            <a class="top-bar-nav-link icon-before icon-storage" href="SITE/dbtools" title="Dev tools (sql, db sync, etc)">Dev tools</a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($manual_url = Kohana::config('branding.manual_url'))): ?>
                        <li class="top-bar-nav-item">
                            <a class="top-bar-nav-link icon-before icon-book" href="<?= Enc::html($manual_url); ?>" target="_blank" title="Manual">Manual</a>
                        </li>
                    <?php endif; ?>
                    <li class="top-bar-nav-item">
                        <button class="top-bar-nav-button icon-before icon-settings" type="button" title="Settings">Settings</button>
                        <div class="dropdown-box top-bar-nav-settings-dropdown">
                            <div class="dropdown-box__text">
                                <p>Admin access</p>
                            </div>
                            <ul class="top-bar-nav-settings-dropdown-list list-style-2">
                                <?php if (AdminPerms::getManageOperatorCategories()): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/operator">Operators</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::canAccess('access_operators')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/per_record_permission">Per-record permissions</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::controllerAccess('action_log', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/action_log">Activity log</a>
                                    </li>
                                <?php endif; ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/docs">Project Documentation</a>
                                    </li>
                            </ul>
                            <div class="dropdown-box__text dropdown-box__text--mid">
                                <p>Content settings</p>
                            </div>
                            <ul class="top-bar-nav-settings-dropdown-list list-style-2">
                                <?php if (AdminPerms::controllerAccess('content_subscribe', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/content_subscription">Content subscriptions</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::controllerAccess('extra_page', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/extra_page">Snippet pages</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::controllerAccess('document_type', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/document_type">Document types</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::controllerAccess('redirect', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/redirect">Redirects</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (AdminPerms::controllerAccess('subsite', 'contents')): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/subsite">Subsites</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php $operator = AdminAuth::getDetails(); ?>
                    <li class="top-bar-nav-item">
                        <button class="top-bar-nav-button icon-before icon-person" type="button" title="Operator <?= Enc::html($operator['name']); ?>">
                            <span class="topbar-nav-button__avatar">
                                <img class="topbar-nav-button__avatar__image" src="https://www.gravatar.com/avatar/<?php echo md5( strtolower( trim( $operator['email'] ) ) ); ?>?s=84&amp;d=blank" alt="">
                            </span> User settings</button>
                        <div class="dropdown-box top-bar-nav-settings-dropdown">

                            <div class="dropdown-box__text">
                                <p><?= Enc::html($operator['name']); ?></p>
                            </div>

                            <ul class="top-bar-nav-settings-dropdown-list list-style-2">
                                <?php if (AdminAuth::hasDatabaseRecord()): ?>
                                    <li class="top-bar-nav-settings-dropdown-list-item">
                                        <a href="admin/intro/my_settings">Settings</a>
                                    </li>
                                <?php endif; ?>
                                <li class="top-bar-nav-settings-dropdown-list-item">
                                    <a href="SITE/admin/logout">Log out</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <header id="header" class="-clearfix">
            <div class="navigation-area">
                <div class="container">
                    <div class="logo sidebar -clearfix"><span class="logo-sprout">Sprout</span> <span class="logo-cms">CMS</span> <span class="logo-version"><?= Enc::html(Sprout::getVersion()); ?></span></div>

                    <div id="navigation" class="mainbar">
                        <?php echo Admin::topNav($controller_name); ?>
                    </div>
                </div>
            </div>
            <div class="sub-header">
                <div class="container">
                    <div class="sidebar sub-header-side">

                    </div>
                    <div class="mainbar sub-header-main">
                        <h1 class="site-title"><?php echo Enc::html(Subsites::getConfigAdmin('site_title')); ?></h1>

                        <a class="sub-header-view-site-button button button-regular button-green icon-after icon-desktop_mac" href="<?php echo Enc::html($live_url); ?>" target="_blank">View live site</a>

                        <?php if (Subsites::hasMultiple()): ?>
                            <!-- Subsite selector version -->
                            <div id="select-site">
                                <?php // echo Enc::html(Subsites::getName($_SESSION['admin']['active_subsite'])); ?>
                                <?php echo Subsites::listSelector($_SESSION['admin']['active_subsite']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>


        <div id="content" class="-clearfix">
            <div class="container">
                <div id="main" class="mainbar mainbar-reverse <?= empty($main_class) ? '' : Enc::html($main_class); ?>">

                    <!-- Main Heading -->
                    <div id="main-heading">

                        <div id="main-heading-options">
                            <?php if ($controller_name == 'page' and strpos(Router::$current_uri, '/edit/') !== false): ?>
                                <button type="button" class="button button-grey button-small icon-after icon-settings page-settings-button page-edit-tab-button" data-target="page-settings-wrapper">Page settings</button>
                                <button type="button" class="button button-grey button-small icon-after icon-history revisions-button page-edit-tab-button" data-target="page-revisions-wrapper">Revisions</button>
                            <?php endif; ?>
                            <?php if (!empty($enable_seo)): ?>
                                <button type="button" class="button button-grey button-small icon-after icon-search seo-button page-edit-tab-button" data-target="seo-wrapper">SEO</button>
                            <?php endif; ?>
                            <?php if (!empty($has_tags)) : ?>
                                <button type="button" class="button button-grey button-small icon-after icon-local_offer tags-button page-edit-tab-button" data-target="tags-wrapper">Tags</button>
                            <?php endif; ?>
                        </div>

                       <?php if ($controller_name !== '_dashboard'): ?>
                            <?php
                            // Encode the title, but preserve STRONG tags as they mark the actual name of the item being edited
                            $main_title = str_replace(['<strong>','</strong>'], ['###strong###','###/strong###'], $main_title);
                            $main_title = Enc::htmlNoDup($main_title);
                            $main_title = str_replace(['###strong###','###/strong###'], ['<strong>','</strong>'], $main_title);

                            echo '<h2>', $main_title, '</h2>';
                            ?>
                       <?php endif; ?>
                    </div>

                    <!-- Main Content -->
                    <div id="main-content" class="-clearfix">
                        <?php echo Notification::checkMessages(); ?>
                        <?php if (!empty($locked)) echo '<ul class="messages all-type-neutral"><li class="neutral">This record currently locked for editing by ', Enc::html($locked['operator_name']), ' as of ', date('g:i a', strtotime($locked['date_modified'])), '</li></ul>'; ?>
                        <?php echo $main_content; ?>
                    </div>

                    <?php require("_footer.php"); ?>
                </div>
                <div id="sidebar" class="sidebar sidebar-reverse">

                    <button type="button" class="sidebar-collapse-button icon-before icon-keyboard_arrow_left" title="Toggle sidebar"><span class="-vis-hidden">Close sidebar</span></button>

                    <div class="sidebar-inner">
                        <?php if ($nav): ?>
                            <!-- Navigation -->
                            <div class="sidebar-box">
                                <h2 class="icon-before icon-insert_drive_file"><?php echo Enc::html($controller_navigation_name); ?></h2>

                                <div class="sidebar-box-content">
                                    <?php echo $nav; ?>
                                </div>

                            </div>
                        <?php endif; ?>

                        <?php if ($nav_tools): ?>
                            <div id="search" class="sidebar-box">
                                <h2 class="icon-before icon-settings">Tools</h2>
                                <div class="sidebar-box-content">
                                    <ul class="list-style-1">
                                        <?php echo implode("\n", $nav_tools); ?>
                                    </ul>
                                </div>

                            </div>
                        <?php endif; ?>
                    </div>

                </div>
                <?php require("_footer.php"); ?>
            </div>
        </div>


</div>



<script type="text/javascript">
$(document).ready(function() { $(document).trigger('done'); });
</script>

</body>
</html>
