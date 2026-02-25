<?php

use Sprout\Helpers\I18n;
use karmabunny\kb\Events;
use Sprout\App;
use Sprout\Events\PreControllerEvent;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;

// Backwards compat.
if (!defined('KOHANA')) {
    define('KOHANA', ENTRYPOINT);
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

$_SERVER['HTTP_HOST'] ??= Kohana::config('config.cli_domain');
$_SERVER['SERVER_NAME'] ??= $_SERVER['HTTP_HOST'];

// Keep old router properties.
Router::$current_uri = Request::findUri();
Router::$query_string = '?' . Request::getQueryString(true);
Router::$complete_uri = Router::$current_uri . Router::$query_string;

Events::on(App::class, function(PreControllerEvent $event) {
    Router::$controller = $event->controller;
    Router::$method = $event->method;
    Router::$arguments = $event->arguments;
});

// Remove the kohana query URI, if present.
if (isset($_GET['kohana_uri'])) {
    unset($_GET['kohana_uri']);
    $_SERVER['QUERY_STRING'] = preg_replace('~\bkohana_uri\b[^&]*+&?~', '', $_SERVER['QUERY_STRING']);
}
