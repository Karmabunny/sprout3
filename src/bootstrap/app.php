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

use karmabunny\kb\Events;
use Sprout\App;
use Sprout\Events\BootstrapEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Events\PostRoutingEvent;
use Sprout\Events\PreRoutingEvent;
use Sprout\Helpers\CoreAdminAuth;
use Sprout\Helpers\Modules;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PageRouting;
use Sprout\Helpers\Services;
use Sprout\Helpers\SessionStats;
use Sprout\Helpers\SubsiteSelector;

Services::register(CoreAdminAuth::class);

// Page routing + display handling.
Events::on(App::class, PreRoutingEvent::class, [PageRouting::class, 'prerouting']);
Events::on(App::class, PostRoutingEvent::class, [PageRouting::class, 'postrouting']);
Events::on(App::class, DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

if (!IN_PRODUCTION AND PHP_SAPI !== 'cli') {
    Events::on(App::class, BootstrapEvent::class, function()  {
        header('x-sprout-tag:' . SPROUT_REQUEST_TAG);
    });
}

// Initialise all modules.
Modules::loadModules('sprout');

// Choose the subsite to use, based on domain, directory, mobile etc.
SubsiteSelector::selectSubsite();

SessionStats::init();

// Initialise Sprout core code.
require APPPATH . '/sprout_load.php';

// Initialise any custom non-module code
if (is_readable(DOCROOT . '/skin/sprout_load.php')) {
    require DOCROOT . '/skin/sprout_load.php';
}

Services::lock();

// Boot up and go.
$app = App::instance();
$app->run();

