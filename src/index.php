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


if (version_compare(PHP_VERSION, '5.5') < 0) {
    exit('PHP 5.5 or newer required');
}

// This file contains a class with a methods for determining the details of
// the very initial environment, prior to the rest of the system coming up
require __DIR__ . '/config/_bootstrap_config.php';

// Define the website environment status. This determines how much debugging
// information is provided when errors occur, among other things.
if (file_exists(__DIR__ . '/config/dev_hosts.php')) {
    require __DIR__ . '/config/dev_hosts.php';
    if (@is_array($dev_hosts)) {
        $dev_hosts = array_filter($dev_hosts);
        if (in_array(php_uname('n'), $dev_hosts)) {
            define('IN_PRODUCTION', false);
        }
    }
    unset($dev_hosts);
}
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

/**
 * Occasionally a server is offline - it does not have internet access, but it
 * is still available through the local network. In this case, the following
 * flag should be changed to FALSE. This will disable external services such
 * as remote login.
 */
define('SERVER_ONLINE', true);

/**
 * Turning off display_errors will effectively disable Kohana error display
 * and logging. You can turn off Kohana errors in sprout/config/config.php
 */
ini_set('display_errors', true);

define('DOCROOT', realpath(__DIR__) . DIRECTORY_SEPARATOR);
define('KOHANA', basename(__FILE__));
define('APPPATH', DOCROOT . 'sprout' . DIRECTORY_SEPARATOR);

// If behind a reverse proxy, make the server think it is the proxy server
if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
}

// CLI-server for development.
if (!IN_PRODUCTION and PHP_SAPI === 'cli-server') {
    $ok = require APPPATH . 'cli_bootstrap.php';
    if ($ok !== null) return $ok;
}

if (file_exists(DOCROOT . 'install.php')) {
    // Load the installation tests
    require DOCROOT . 'install.php';
} else {
    // Initialize Kohana
    require APPPATH . 'core/Bootstrap.php';
}
