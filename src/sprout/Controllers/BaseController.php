<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */

namespace Sprout\Controllers;

use Exception;
use karmabunny\kb\Events;
use Kohana;
use ReflectionException;
use ReflectionMethod;
use Sprout\Helpers\ModuleInterface;
use Sprout\Helpers\Modules;
use Sprout\Events\AfterActionEvent;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\BeforeActionEvent;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Text;

/**
 * This is a true base controller.
 *
 * It does nothing but base things.
 *
 * @package Sprout\Controllers
 */
abstract class BaseController
{

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;

    /**
     * @return  void
     */
    public function __construct()
    {
        if (Kohana::$instance == NULL)
        {
            // Set the instance to the first controller loaded
            Kohana::$instance = $this;
        }
    }


    /**
     * The router/kohana will invoke this method to invoke an action.
     *
     * If you please, you may wrap this method to create before/after hooks.
     *
     * @param mixed $method
     * @param mixed $args
     * @return mixed
     */
    public function _run($method, $args)
    {
        try {
            $reflect = new ReflectionMethod($this, $method);

            // Do not allow access to hidden methods
            if ($method[0] === '_') {
                throw new ReflectionException('hidden controller method');
            }

            // Do not attempt to invoke protected methods
            if ($reflect->isProtected() or $reflect->isPrivate()) {
                throw new ReflectionException('protected controller method');
            }
        }
        catch (ReflectionException $exception) {
            $event = new NotFoundEvent();
            Events::trigger(Kohana::class, $event);
            return;
        }

        $event = new BeforeActionEvent([
            'sender' => $this,
            'method' => $method,
            'arguments' => $args,
        ]);

        Events::trigger(BaseController::class, $event);

        if ($event->cancelled) {
            return null;
        }

        $response = $this->$method(...$args);

        $event = new AfterActionEvent(['result' => $response]);
        Events::trigger(BaseController::class, $event);
        $response = $event->result;

        return $response;
    }


    /**
     * Handles methods that do not exist.
     *
     * @param   string  method name
     * @param   array   arguments
     * @return  void
     * @throws  \BadMethodCallException
     */
    public function __call($method, $args)
    {
        throw new \BadMethodCallException("Method '{$method}' not found");
    }


    /**
     * Get the module that this controller belongs to.
     *
     * If the controllers belongs to Sprout itself, this returns null.
     *
     * @return ModuleInterface|null
     */
    public function getModule(): ?ModuleInterface
    {
        return Modules::getModuleForClass($this);
    }


    /**
     * Get the absolute path of the current module for this controller.
     *
     * TODO this might be too broad. It's only used for loading JSON forms
     * and perhaps encourages bad behaviour. Such as assuming common path
     * structures for all modules, where instead this should be written into
     * the module class.
     *
     * @return string
     */
    public function getAbsModulePath(): string
    {
        $module = $this->getModule();

        // Assume it's a core sprout controller.
        if (!$module) {
            return rtrim(APPPATH, '/');
        }

        return $module->getPath();
    }


    /**
     * Get the a prefix suitable for finding views for this controller.
     *
     * Do not assume that all modules, or even core sprout live relative to
     * each other or the DOCROOT (as they previously did).
     *
     * TODO rename this - like `getViewPrefix()`
     *
     * @return string 'sprout' or 'modules/AwesomeModule'
     */
    public function getModulePath(): string
    {
        $module = $this->getModule();

        // Assume it's a core sprout controller.
        if (!$module) {
            return 'sprout';
        }

        return 'modules/' . $module->getName();
    }


    /**
     * Return the class name for this controller, expressed in CSS style, i.e. with dashes
     *
     * Example: When called from BlogPostController --> 'blog-post-controller'
     *
     * @return string Name of this PHP class, in a format suitable for use in CSS
     */
    public function getCssClassName()
    {
        $class_name = Sprout::removeNs(get_class($this));
        $class_name = Text::camel2lc($class_name);
        $class_name = str_replace('_', '-', $class_name);
        return $class_name;
    }
}