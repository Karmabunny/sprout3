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

use Sprout\Helpers\Errors;

// This file contains a class with a methods for determining the details of
// the very initial environment, prior to the rest of the system coming up
@include DOCROOT . 'config/_bootstrap_config.php';

// But if it's not found, then just use the default.
if (!class_exists(BootstrapConfig::class)) {
    require __DIR__ . '/BootstrapConfig.php';
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

Errors::$ENABLE_FATAL_ERRORS = (
    defined('BootstrapConfig::ENABLE_FATAL_ERRORS')
    and constant('BootstrapConfig::ENABLE_FATAL_ERRORS')
);
