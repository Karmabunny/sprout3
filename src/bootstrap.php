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

define('COREPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', COREPATH . 'sprout' . DIRECTORY_SEPARATOR);

if (!defined('VENDOR_PATH')) {
    define('VENDOR_PATH', BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR);
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', BASE_PATH . 'storage' . DIRECTORY_SEPARATOR);
}

if (!defined('DOCROOT')) {
    define('DOCROOT', BASE_PATH . 'src' . DIRECTORY_SEPARATOR);
}

if (!defined('WEBROOT')) {
    define('WEBROOT', BASE_PATH . 'web' . DIRECTORY_SEPARATOR);
}

if (!defined('KOHANA')) {
    define('KOHANA', 'index.php');
}

ini_set('display_errors', '1');

// Code editor hinting.
// This is actually defined in phpunit.dist.xml.
if (false) {
    define('PHPUNIT', 0);
}

// Load the environment from a file - if available.
if (file_exists(BASE_PATH . '.env')) {
    \Dotenv\Dotenv::create(BASE_PATH)->load();
}

// Default environment is 'dev'.
// All upgraded sites must set their environments appropriately.
define('SITES_ENVIRONMENT', getenv('SITES_ENVIRONMENT') ?: 'dev');
define('IN_PRODUCTION', SITES_ENVIRONMENT === 'prod');


// This file contains a class with a methods for determining the details of
// the very initial environment, prior to the rest of the system coming up
@include DOCROOT . 'config/_bootstrap_config.php';

// But if it's not found, then just use the default.
if (!class_exists(BootstrapConfig::class)) {
    require __DIR__ . '/bootstrap/BootstrapConfig.php';
}

// Set the error reporting level.
// First check defined() so we don't break migrations.
if (defined('BootstrapConfig::ERROR_REPORTING')) {
    error_reporting(constant('BootstrapConfig::ERROR_REPORTING'));
}
else if (IN_PRODUCTION) {
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    error_reporting(-1);
}

// The timezone is explicitly set to avoid warnings from bad server configuration
if (!empty(BootstrapConfig::TIMEZONE)) {
    date_default_timezone_set(BootstrapConfig::TIMEZONE);
}

// This should be defined in the app index.php.
if (!defined('SERVER_ONLINE')) {
    define('SERVER_ONLINE', true);
}


// Running tests.
if (defined('PHPUNIT') and PHPUNIT) {
    require __DIR__ . '/bootstrap/phpunit.php';
    return;
}

// CLI-server for development.
if (PHP_SAPI === 'cli-server') {
    $ok = require __DIR__ . '/bootstrap/cliserver.php';
    return $ok;
}

// Web contexts.
require __DIR__ . '/bootstrap/web.php';
