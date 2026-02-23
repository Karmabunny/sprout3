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

namespace Sprout\Welcome;

use karmabunny\kb\Events;
use Sprout\App;
use Sprout\Events\BootstrapEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Helpers\Config;
use Sprout\Helpers\Module;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\SubsiteSelector;

/**
 * The welcome module.
 */
class WelcomeModule extends Module
{

    /** @inheritdoc */
    public function getVersion(): string
    {
        return Sprout::getVersion(true);
    }


    public function loadSprout(): void
    {
        // Replace all routes with our own.
        Events::on(App::class, function(BootstrapEvent $event) {
            $event->routes = Config::include($this->getPath() . 'config/routes.php');
        });

        // Still needs display handling.
        Events::on(App::class, DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

        // Force default subsite.
        SubsiteSelector::$subsite_id = 1;
        SubsiteSelector::$content_id = 1;
        SubsiteSelector::$subsite_code = 'default';

        $app = App::instance();
        $app->run();
    }
}
