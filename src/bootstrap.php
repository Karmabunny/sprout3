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

define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'sprout' . DIRECTORY_SEPARATOR);

if (!defined('KOHANA')) {
    define('KOHANA', 'index.php');
}

// Code editor hinting.
// This is actually defined in phpunit.dist.xml.
if (false) {
    define('PHPUNIT', 0);
}

// If not already defined by the application.
if (!class_exists(BootstrapConfig::class)) {
    require __DIR__ . '/bootstrap/BootstrapConfig.php';
}

// The timezone is explicitly set to avoid warnings from bad server configuration
if (!empty(BootstrapConfig::TIMEZONE)) {
    date_default_timezone_set(BootstrapConfig::TIMEZONE);
}

// Default is in production.
if (!defined('IN_PRODUCTION')) {
    define('IN_PRODUCTION', true);
}

// All errors need to be fixed before code goes into production
if (IN_PRODUCTION) {
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

// If behind a reverse proxy, make the server think it is the proxy server
if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
}

// Running tests.
if (defined('PHPUNIT') and PHPUNIT) {
    require __DIR__ . '/bootstrap/phpunit.php';
    return;
}

// CLI-server for development.
if (!IN_PRODUCTION and PHP_SAPI === 'cli-server') {
    $ok = require __DIR__ . '/bootstrap/cliserver.php';
    return $ok;
}

// Web contexts.
require __DIR__ . '/bootstrap/web.php';
