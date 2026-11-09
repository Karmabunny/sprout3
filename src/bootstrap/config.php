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

use Sprout\Helpers\Config;

// Load core configs first to register modules.
// Modules are not yet activated, but their configs can now modify other configs.
Config::get('config');

// This file contains a class with a methods for determining the details of
// the very initial environment, prior to the rest of the system coming up
@include DOCROOT . 'config/_bootstrap_config.php';

// Override configs for backwards compat.
if (class_exists('BootstrapConfig')) {
    Config::set('config.error_reporting', constant('BootstrapConfig::ERROR_REPORTING'));
    Config::set('config.enable_fatal_errors', constant('BootstrapConfig::ENABLE_FATAL_ERRORS'));
    Config::set('config.enable_media_cache', constant('BootstrapConfig::ENABLE_MEDIA_CACHE'));
    Config::set('locale.timezone', constant('BootstrapConfig::TIMEZONE'));
}

error_reporting(Config::get('config.error_reporting'));

// The timezone is explicitly set to avoid warnings from bad server configuration
date_default_timezone_set(Config::get('locale.timezone'));
