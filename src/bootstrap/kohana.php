<?php

use Sprout\Helpers\I18n;

if (!defined('KOHANA')) {
    define('KOHANA', 'index.php');
}

// Backwards compat.
if (!defined('SITES_ENVIRONMENT')) {
    /** @deprecated use ENVIRONMENT */
    define('SITES_ENVIRONMENT', ENVIRONMENT);
}

// Old error constants.
define('E_KOHANA', 42);
define('E_PAGE_NOT_FOUND', 43);
define('E_DATABASE_ERROR', 44);

// Load core files
require APPPATH . 'core/utf8.php';
require APPPATH . 'core/Event.php';
require APPPATH . 'core/Kohana.php';

Kohana::$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
Kohana::$locale = I18n::getLanguage();
Kohana::setup();

$_SERVER['HTTP_HOST'] ??= Kohana::config('config.cli_domain');
$_SERVER['SERVER_NAME'] ??= $_SERVER['HTTP_HOST'];
