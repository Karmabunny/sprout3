<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\I18n;
use Sprout\Helpers\Register;
use Sprout\Helpers\WidgetArea;
use Sprout\Helpers\Pdb;


I18n::init();

Register::extraPage(1, '404 error');
Register::extraPage(2, 'Admin login message');

Register::pageattr('sprout.notes', 'Notes', 'AttrEditorMultiline');
Register::pageattr('sprout.admin_notes', 'Admin notes', 'AttrEditorMultiline');
Register::pageattr('sprout.department', 'Department');
Register::pageattr('sprout.document_no', 'Document No.');
Register::pageattr('sprout.maintainer', 'Maintainer');
Register::pageattr('sprout.orig_author', 'Orig. Author');
Register::pageattr('sprout.lang', 'Language');

Register::linkspec('\\Sprout\\Helpers\\LinkSpecExternal', 'External URL');
Register::linkspec('\\Sprout\\Helpers\\LinkSpecInternal', 'Internal URL');
Register::linkspec('\\Sprout\\Helpers\\LinkSpecPage', 'Internal Page');
Register::linkspec('\\Sprout\\Helpers\\LinkSpecDocument', 'Document');

Register::rteLibrary('\\Sprout\\Helpers\\RteLibraryPages');
Register::rteLibrary('\\Sprout\\Helpers\\RteLibraryDocuments');
Register::rteLibrary('\\Sprout\\Helpers\\RteLibrarySounds');
Register::rteLibrary('\\Sprout\\Helpers\\RteLibraryImages');

Register::sitemapGen('\\Sprout\\Helpers\\SitemapGenPages');

Register::frontEndController('Sprout\\Controllers\\AdvancedSearchController', 'Advanced search');

Register::contentReplace('inner_html', ['Sprout\\Helpers\\ContentReplace', 'intlinks']);
Register::contentReplace('inner_html', ['Sprout\\Helpers\\ContentReplace', 'localAnchor']);

Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\PageAdminController', 'cronPageActivate');
Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\PageAdminController', 'cronPageDeactivate');
Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\PageAdminController', 'cronCheckStale');
Register::cronJob('daily', 'Sprout\\Controllers\\AdminController', 'cronGenericActivate');
Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\FileAdminController', 'cronCleanupInvalid');
Register::cronJob('daily', 'Sprout\\Controllers\\ContentSubscribeController', 'cronSendSubscriptions');
Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\ActionLogAdminController', 'cronCleanup');

Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Platform\\DeviceCategory', 'Platform', 'Device category');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Platform\\BrowserName', 'Platform', 'Browser name');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Platform\\BrowserVersion', 'Platform', 'Browser version');

Register::widgetTile(
    'embedded',
    'Text blocks',
    'insert_drive_file',
    'Formatted page content',
    [
        'RichText' => 'Rich text',
    ]
);


Register::widgetTile(
    'embedded',
    'Collections',
    'description',
    'Formatted page content',
    [
        'ChildrenPages' => 'Page lists',
        'ChildrenGallery' => 'Page gallery',
        'ImageGallery' => 'Image gallery',
        'FileList' => 'List of files',
        'Sitemap' => 'Sitemap',
    ]
);

Register::widgetTile(
    'embedded',
    'Maps',
    'map',
    'Embed Google maps',
    [
        'Map' => 'Google map',
        'MapDirections' => 'Google map with directions',
    ]
);

Register::widgetTile(
    'embedded',
    'Advanced',
    'settings',
    'Stuff most people won\'t touch',
    [
        'HtmlCode' => 'HTML code',
    ]
);

Register::widgetTile(
    'embedded',
    'Social Media',
    'settings',
    'Social',
    [
        'RssFeedWidget' => 'RSS Feed',
    ]
);

Register::widgetTile(
    'sidebar',
    'Text blocks',
    'insert_drive_file',
    'Page content',
    [
        'RichText' => 'Rich text',
        'HtmlCode' => 'HTML code',
    ]
);

Register::widgetTile(
    'sidebar',
    'Collections',
    'description',
    'Formatted page content',
    [
        'ChildrenPages' => 'Children pages',
        'ImageGallery' => 'Image gallery',
        'FileList' => 'List of files',
    ]
);

$area = WidgetArea::findAreaByName('email');
if ($area) {
    $area->addWidget('HtmlCode');
}

Register::emailText(
    'operator.welcome',
    array(
        'name' => 'The real name of the new operator',
        'username' => 'The username of the new operator',
        'password' => 'The password for logging in',
    ),
    'sprout/email/operator_welcome'
);


Pdb::setFormatter('DateTime', function($dt) {
    return $dt->format('Y-m-d H:i:s');
});

Pdb::setFormatter('DateInterval', function($interval) {
    $dt = new \DateTime();
    $dt->add($interval);
    return $dt->format('Y-m-d H:i:s');
});
