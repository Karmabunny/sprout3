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

// Catching max_input_vars and other pre-execution errors.
// see https://stackoverflow.com/a/21601349/1688568
$e0 = error_get_last();

// A big bunch of core constants.
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('VENDOR_PATH', BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', BASE_PATH . 'storage' . DIRECTORY_SEPARATOR);
define('DOCROOT', BASE_PATH . 'src' . DIRECTORY_SEPARATOR);
define('WEBROOT', BASE_PATH . 'web' . DIRECTORY_SEPARATOR);
define('KOHANA', basename(__FILE__));

ini_set('display_errors', '1');

require VENDOR_PATH . 'autoload.php';
require VENDOR_PATH . 'sproutcms/cms/src/bootstrap.php';
