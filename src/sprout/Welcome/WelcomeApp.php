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

use karmabunny\router\Router;
use Sprout\Controllers\BaseController;
use Sprout\Core\BaseApp;
use Sprout\Events\DisplayEvent;
use Sprout\Helpers\Config;
use Sprout\Helpers\CoreAdminAuth;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Services;
use Sprout\Helpers\SubsiteSelector;

/**
 * A mini application for the welcome system.
 */
class WelcomeApp extends BaseApp
{

    /** @inheritdoc */
    protected function init()
    {
        parent::init();

        $config = Config::get('config.router');
        $this->router = Router::create($config);
        $this->routes = Config::load(__DIR__ . '/config/routes.php');

        $this->controller = BaseController::class;

        $this->on(DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

        Services::register(CoreAdminAuth::class);
        Services::lock();

        SubsiteSelector::setSubsite(['id' => 1, 'code' => 'default']);
    }
}
