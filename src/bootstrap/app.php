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
use Sprout\SproutApp;
use Sprout\Events\BootstrapEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Events\PostRoutingEvent;
use Sprout\Events\PreRoutingEvent;
use Sprout\Helpers\Config;
use Sprout\Helpers\CoreAdminAuth;
use Sprout\Helpers\Modules;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PageRouting;
use Sprout\Helpers\Services;
use Sprout\Helpers\SessionStats;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Url;

Services::register(CoreAdminAuth::class);

// Page routing + display handling.
Events::on(SproutApp::class, PreRoutingEvent::class, [PageRouting::class, 'prerouting']);
Events::on(SproutApp::class, PostRoutingEvent::class, [PageRouting::class, 'postrouting']);
Events::on(SproutApp::class, DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

if (Config::get('core.hide_index')) {
    Events::on(SproutApp::class, PreRoutingEvent::class, function(PreRoutingEvent $event) {
        if ($event->uri === ENTRYPOINT) {
            Url::redirect(Url::base(false, true), 301);
        }
    });
}

if (!IN_PRODUCTION AND PHP_SAPI !== 'cli') {
    Events::on(SproutApp::class, BootstrapEvent::class, function()  {
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
$app = SproutApp::instance();
$app->run();

