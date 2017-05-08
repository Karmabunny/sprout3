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
define('IN_PRODUCTION', BootstrapConfig::isInProduction());

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

/**
 * This is designed to auto-detect the 'site_domain' config option
 * Basically it looks at where index.php is located,
 * and compares that with the DOCUMENT_ROOT path provided by the web server
 * If there is a match, it stips off the DOCUMENT_ROOT and index.php
 * bits, leaving the value that is needed
 *
 * If the detected site-domain begins with /v1 (or v2, v3...v9) and the
 * REQUEST_URI does not begin with v1, the v1 part will be stripped from the
 * site-domain. This obviously only works on servers which support REQUEST_URI,
 * so Microsoft IIS is out - but I don't expect rewriting out parts of URIs
 * to be easy with that server anyway :P
 * @return string The root web path, e.g. '/' or '/my-subsite/'
 * @return bool False if the path couldn't be determined
 */
function _privDetermineWebDirectory() {
    if (!empty($_SERVER['PHP_S_WEBDIR'])) return $_SERVER['PHP_S_WEBDIR'];
    if (! $_SERVER['DOCUMENT_ROOT']) return false;

    $pos = strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']);
    if ($pos === 0) {
        $doc_path = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
        $doc_path = dirname($doc_path);
        $doc_path = str_replace('\\', '/', $doc_path);

        if (substr($doc_path, 0, 1) != '/') $doc_path = '/' . $doc_path;
        if (substr($doc_path, -1, 1) != '/') $doc_path .= '/';

        if ($_SERVER['REQUEST_URI'] and preg_match('!^/v[1-9]/!', $doc_path) and !preg_match('!^/v[1-9]/!', $_SERVER['REQUEST_URI'])) {
            $doc_path = preg_replace('!^/v[1-9]/!', '/', $doc_path);
        }

        return $doc_path;
    }

    return false;
}

if (file_exists(DOCROOT . 'install.php')) {
    // Load the installation tests
    require DOCROOT . 'install.php';
} else {
    // Initialize Kohana
    require APPPATH . 'core/Bootstrap.php';
}
