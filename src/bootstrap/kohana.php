<?php

use Sprout\Helpers\I18n;

// Backwards compat.
if (!defined('KOHANA')) {
    define('KOHANA', ENTRYPOINT);
}

// Backwards compat.
if (!defined('SITES_ENVIRONMENT')) {
    /** @deprecated use ENVIRONMENT */
    define('SITES_ENVIRONMENT', ENVIRONMENT);
}

Kohana::$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
Kohana::$locale = I18n::getLanguage();
Kohana::setup();

$_SERVER['HTTP_HOST'] ??= Kohana::config('config.cli_domain');
$_SERVER['SERVER_NAME'] ??= $_SERVER['HTTP_HOST'];
