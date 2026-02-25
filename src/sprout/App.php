<?php
/*
 * Copyright (C) 2026 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout;

use karmabunny\kb\Events;
use karmabunny\router\Action;
use karmabunny\router\Router;
use Sprout\Controllers\BaseController;
use Sprout\Core\BaseApp;
use Sprout\Events\BootstrapEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\PostRoutingEvent;
use Sprout\Events\PreRoutingEvent;
use Sprout\Exceptions\HttpException;
use Sprout\Helpers\Config;
use Sprout\Helpers\CoreAdminAuth;
use Sprout\Helpers\Modules;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PageRouting;
use Sprout\Helpers\Request;
use Sprout\Helpers\Services;
use Sprout\Helpers\SessionStats;
use Sprout\Helpers\SubsiteSelector;

/**
 * The Sprout application.
 */
class App extends BaseApp
{

    /** @inheritdoc */
    protected function init()
    {
        parent::init();

        $config = Config::get('config.router');
        $this->router = Router::create($config);
        $this->routes = Config::get('routes');

        $this->controller = BaseController::class;

        // Page routing + display handling.
        $this->on(PreRoutingEvent::class, [PageRouting::class, 'prerouting']);
        $this->on(PostRoutingEvent::class, [PageRouting::class, 'postrouting']);
        $this->on(DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

        if (!IN_PRODUCTION AND PHP_SAPI !== 'cli') {
            $this->on(BootstrapEvent::class, function()  {
                header('x-sprout-tag:' . SPROUT_REQUEST_TAG);
            });
        }

        Services::register(CoreAdminAuth::class);

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
    }


    /** @inheritdoc */
    public function resolveRequest(): array
    {
        $method = Request::method();
        $uri = Request::findUri();
        return [$method, $uri];
    }


    /** @inheritdoc */
    public function resolveAction(string $method, string $uri): ?Action
    {
        if ($uri === '') {
            $uri = '_default';
        }

        $action = parent::resolveAction($method, $uri);

        // Convert regex syntax into target + args.
        if (
            $action !== null
            and is_string($action->target)
            and preg_match('!^([^/]+)/(.*)$!', $action->target, $matches)
        ) {
            [, $class, $arguments] = $matches;

            // Compat.
            if (strpos($class, '\\') === false) {
                $class = 'Sprout\\Controllers\\' . $class;
            }

            if (strpos($arguments, '$') !== false) {
                $arguments = preg_replace('#^' . $action->rule . '$#u', $arguments, $action->path);
            }

            $arguments = explode('/', $arguments);
            $method = array_shift($arguments);

            // Rewrite the action target + args.
            $action->target = [$class, $method];
            $action->args = $arguments;
        }

        return $action;
    }


    /** @inheritdoc */
    public function notFound()
    {
        $event = new NotFoundEvent();
        Events::trigger(self::class, $event);

        if (!$event->handled) {
            throw new HttpException(404);
        }

        parent::notFound();
    }

}
