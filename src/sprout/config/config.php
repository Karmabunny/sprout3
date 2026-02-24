<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

/**
 * The Sprout application class.
 */
$config['app'] = \Sprout\SproutApp::class;

/**
 * The PHP error reporting level.
 */
if (IN_PRODUCTION) {
    $config['error_reporting'] = E_ALL ^ E_NOTICE;
}
else {
    $config['error_reporting'] = E_ALL;
}

/**
 * Whether to process fatal errors with the shutdown handler.
 *
 * Else uses native display_errors.
 */
$config['enable_fatal_errors'] = true;

/**
 * Copy media assets into the target web folder.
 *
 * This assumes the web server (nginx/apache) will pick up the real file
 * before it defers to the PHP application.
 */
$config['enable_media_cache'] = IN_PRODUCTION;

/**
 * Force a default protocol to be used by the site. If no site_protocol is
 * specified, then the current protocol is used, or when possible, only an
 * absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = '';

/**
 * Base path of the web site.
 * Most common value would be '/' but it may be something else instead.
 * Should always have a trailing slash.
 */
$config['site_domain'] = '/';

/**
 * When PHP is called in CLI mode (e.g. a cron script), it cannot
 * autodetect the host name, so you need to specify it here.
 *
 * Examples:
 *   example.com
 *   test.example.com
 */
$config['cli_domain'] = gethostname();

/**
 * Name of the front controller for this application. Default: index.php
 *
 * This can be removed by using URL rewriting.
 */
if (PHP_SAPI === 'cgi') {
    $config['index_page'] = 'index.php';
} else {
    $config['index_page'] = '';
}

/**
 * Redirect if KOHANA is found in the request_uri.
 */
$config['hide_index'] = false;

/**
 * Fake file extension that will be added to all generated URLs. Example: .html
 */
$config['url_suffix'] = '';

/**
 * Configure the router component.
 *
 * {@see \karmabunny\router\RouterConfig} for more.
 */
$config['router'] = [
    'case_insensitive' => true,
    'extract' => 'attributes|convert|prefixes|nested',
    'mode' => 'regex',
];

/**
 * Whether the server is behind a load balancer
 *
 * This is necessary to determine whether to trust X-Forwarded headers.
 */
$config['load_balanced'] = false;

/**
 * Sprout version is in another file too.
 */
require_once APPPATH . '/version.php';

