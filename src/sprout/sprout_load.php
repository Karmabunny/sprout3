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

use Sprout\Helpers\FindReplaceHtmlCode;
use Sprout\Helpers\FindReplaceRichText;
use Sprout\Helpers\I18n;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\FindReplaceText;
use Sprout\Helpers\Register;
use Sprout\Helpers\SessionStats;
use Sprout\Helpers\WidgetArea;


I18n::init();
SessionStats::init();

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

Register::searchHandler('Sprout\\Controllers\\PageController', 'page_keywords', ['main.active = 1', 'main.show_in_nav = 1']);

Register::frontEndController('Sprout\\Controllers\\AdvancedSearchController', 'Advanced search');

Register::contentReplace('inner_html', ['Sprout\\Helpers\\ContentReplace', 'intlinks']);
Register::contentReplace('inner_html', ['Sprout\\Helpers\\ContentReplace', 'localAnchor']);

Register::contentReplace('main_content', ['Sprout\\Widgets\\ImageGalleryWidget', 'contentReplace']);

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
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Session\\TimeOnSite', 'Session', 'Minutes on site');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Session\\ThisPageviews', 'Session', 'Pageviews');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Session\\TotalPageviews', 'Session', 'Pageviews (total)');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Session\\UniquePageviews', 'Session', 'Pageviews (unique)');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Acquisition\\UtmSource', 'Acquisition', 'Source');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Acquisition\\UtmMedium', 'Acquisition', 'Medium');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Acquisition\\UtmCampaign', 'Acquisition', 'Campaign');
Register::displayCondition('Sprout\\Helpers\\DisplayConditions\\Acquisition\\Referrer', 'Acquisition', 'Full referrer');

Register::findReplace([
    new FindReplaceRichText(),
    new FindReplaceHtmlCode(),
    new FindReplaceText('pages', 'name'),
    new FindReplaceText('pages', 'meta_description'),
    new FindReplaceText('page_widgets', 'heading'),
    new FindReplaceText('extra_pages', 'text'),
]);

Register::widgetTile(
    'embedded',
    'Text blocks',
    'insert_drive_file',
    'Formatted page content',
    [
        'PageColumns' => 'Page columns',
        'RichText' => 'Text block',
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
        'VideoPlaylist' => 'Video play-list gallery'
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

Register::addDbtoolsApi([
    'title' => 'QR Code',
    'desc' => 'Renders QR code',
    'class' => 'Sprout\\Controllers\\DbToolsController',
    'method' => 'qrCodeForm',
]);
