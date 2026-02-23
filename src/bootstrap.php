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

use karmabunny\kb\Uuid;

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
// @phpstan-ignore-next-line
if (false) {
    define('PHPUNIT', 0);
}

// Default environment is 'dev'.
// All upgraded sites must set their environments appropriately.
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('SITES_ENVIRONMENT') ?: 'dev');
}

define('IN_PRODUCTION', ENVIRONMENT === 'prod');

// Backwards compat.
if (!defined('SITES_ENVIRONMENT')) {
    /** @deprecated use ENVIRONMENT */
    define('SITES_ENVIRONMENT', ENVIRONMENT);
}

if (!defined('WORKER_PHP_BIN') and getenv('SITES_PHP_BIN')) {
    define('WORKER_PHP_BIN', getenv('SITES_PHP_BIN'));
}

// This should be defined in the app index.php.
if (!defined('SERVER_ONLINE')) {
    define('SERVER_ONLINE', true);
}

// Define Kohana error constant
define('E_KOHANA', 42);

// Define 404 error constant
define('E_PAGE_NOT_FOUND', 43);

// Define database error constant
define('E_DATABASE_ERROR', 44);

// Define application start time.
define('SPROUT_REQUEST_TIME', microtime(TRUE));

// Define a global request tag.
define('SPROUT_REQUEST_TAG', Uuid::uuid4());

// Set HTTP_HOST for CLI scripts
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['PHP_S_HTTP_HOST'] ?? null;
}

// If behind a reverse proxy, make the server think it is the proxy server
if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
}

// Set SERVER_NAME if it's not set
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
}

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

// Running tests.
if (defined('PHPUNIT') and PHPUNIT) {
    require __DIR__ . '/bootstrap/phpunit.php';
    return;
}

// CLI-server for development.
if (PHP_SAPI === 'cli-server') {
    $ok = require __DIR__ . '/bootstrap/cliserver.php';
    if (!$ok) return false;
}

// Bootstrap the application.
require APPPATH . 'core/Bootstrap.php';
return true;
