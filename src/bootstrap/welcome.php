<?php
/*
 * Copyright (C) 2026 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Modules;
use Sprout\Welcome\WelcomeApp;

// Mini version of framework when using the welcome system
// that avoids lots of code paths which use a database.
if ($welcome = Modules::getModule('Welcome')) {
    $app = WelcomeApp::instance();
    $app->run();
}
