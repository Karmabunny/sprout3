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
use Sprout\Events\NotFoundEvent;
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

        return $this->$method(...$args);
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
     * Get the absolute path of the current module for this controller.
     *
     * @return string|false
     */
    public function getAbsModulePath()
    {
        $path = Sprout::determineFilePath(static::class);

        if (!$path) {
            return false;
        }

        if (strpos($path, APPPATH) === 0) {
            return APPPATH;
        }

        if (strpos($path, DOCROOT . 'modules/') === 0) {
            $path = substr($path, strlen(DOCROOT . 'modules/'));
            [$path] = explode('/', $path, 2);
            return DOCROOT . 'modules/' . $path;
        }

        return false;
    }


    /**
     * Gets the relative path to the module the controller lives in, or sprout itself
     *
     * @return string 'sprout' or 'modules/AwesomeModule'
     */
    public function getModulePath()
    {
        $path = Sprout::determineFilePath(static::class);

        if (!$path) {
            throw new Exception("Where am I?");
        }

        if (strpos($path, APPPATH) === 0) {
            return 'sprout';
        }

        if (strpos($path, DOCROOT . 'modules/') === 0) {
            $path = substr($path, strlen(DOCROOT . 'modules/'));
            [$path] = explode('/', $path, 2);
            return 'modules/' . $path;
        }

        throw new Exception("Where am I?");
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