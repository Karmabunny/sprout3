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

// Load the installation tests
if (file_exists(DOCROOT . 'install.php')) {
    require DOCROOT . 'install.php';
    return;
}

// If behind a reverse proxy, make the server think it is the proxy server
if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
}

// Initialize Kohana
require APPPATH . 'core/Bootstrap.php';
