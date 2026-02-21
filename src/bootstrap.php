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

use karmabunny\kb\HttpStatus;
use karmabunny\kb\Uuid;
use Sprout\Exceptions\HttpException;
use Sprout\Helpers\Errors;
use Sprout\Helpers\I18n;
use Sprout\Helpers\Utf8;

ini_set('display_errors', '1');

define('COREPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', COREPATH . 'sprout' . DIRECTORY_SEPARATOR);

if (!defined('ENTRYPOINT')) {
    define('ENTRYPOINT', basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['argv'][0] ?? 'index.php'));
}

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

if (!defined('PHPUNIT')) {
    define('PHPUNIT', false);
}

if (!defined('BOOTSTRAP_ONLY')) {
    define('BOOTSTRAP_ONLY', false);
}

// Default environment is 'dev'.
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('SITES_ENVIRONMENT') ?: 'dev');
}

define('IN_PRODUCTION', ENVIRONMENT === 'prod');

if (!defined('WORKER_PHP_BIN') and getenv('SITES_PHP_BIN')) {
    define('WORKER_PHP_BIN', getenv('SITES_PHP_BIN'));
}

if (!defined('SERVER_ONLINE')) {
    define('SERVER_ONLINE', true);
}

// Define application start time.
define('SPROUT_REQUEST_TIME', microtime(TRUE));

// Define a global request tag.
define('SPROUT_REQUEST_TAG', Uuid::uuid4());

// Set HTTP_HOST for CLI scripts
if (empty($_SERVER['HTTP_HOST'])) {
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

require __DIR__ . '/bootstrap/config.php';

Utf8::setup();
I18n::init();

@mkdir(STORAGE_PATH . 'cache', 0755, true);
@mkdir(STORAGE_PATH . 'temp', 0755, true);
@mkdir(STORAGE_PATH . 'logs', 0755, true);

require __DIR__ . '/bootstrap/kohana.php';

// Running tests.
if (PHPUNIT) {
    require __DIR__ . '/bootstrap/phpunit.php';
    return;
}

// Skip.
if (BOOTSTRAP_ONLY) {
    return;
}

// CLI-server for development.
if (PHP_SAPI === 'cli-server') {
    $ok = require __DIR__ . '/bootstrap/cliserver.php';
    if (!$ok) return false;
}

// Error handling.
set_error_handler([Errors::class, 'errorHandler']);
set_exception_handler([Errors::class, 'exceptionHandler']);
register_shutdown_function([Errors::class, 'handleFatalErrors']);

ini_set('display_errors', Errors::$ENABLE_FATAL_ERRORS ? '0' : '1');

// TODO make this configurable.
ini_set('error_log', STORAGE_PATH . 'logs/php.log');
ini_set('log_errors', '1');

// Now that we have an exception handler - check for pre-execution errors.
if (isset($e0)) {
    throw new ErrorException($e0['message'], 0, $e0['type'], $e0['file'], $e0['line']);
}

// Handle Apache error codes.
if ($status = (int) ($_GET['_apache_error'] ?? 0)) {
    $error = HttpStatus::toString($status);
    throw new HttpException($status, $error);
}

// Bootstrap the application.
require APPPATH . 'core/Bootstrap.php';
return true;