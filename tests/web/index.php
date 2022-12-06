<?php

// A big bunch of core constants.
define('BASE_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('VENDOR_PATH', BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', BASE_PATH . 'tests/storage' . DIRECTORY_SEPARATOR);
define('DOCROOT', BASE_PATH . 'tests' . DIRECTORY_SEPARATOR);
define('WEBROOT', BASE_PATH . 'tests/web' . DIRECTORY_SEPARATOR);
define('KOHANA', basename(__FILE__));

ini_set('display_errors', '1');

require VENDOR_PATH . 'autoload.php';
require BASE_PATH . 'src/bootstrap.php';
