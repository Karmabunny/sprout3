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

use Event;
use Exception;
use Kohana;
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
     * @return void
     */
    public function _run($method, $args)
    {
        $this->$method(...$args);
    }


    /**
     * Handles methods that do not exist.
     *
     * @param   string  method name
     * @param   array   arguments
     * @return  void
     */
    public function __call($method, $args)
    {
        // If this method is called directly as a result of a bad URL or route, a 404 error is reported
        $bt = debug_backtrace();
        if ($bt[1]['function'] === 'invokeArgs' and $bt[1]['class'] === 'ReflectionMethod') {
            Event::run('system.404');
            return;
        }

        // In every other case, the missing method should be reported
        throw new \Exception("Method '{$method}' not found");
    }


    /**
     * Get the absolute path of the current module for this controller.
     *
     * @return string|false
     */
    public function getAbsModulePath()
    {
        return Sprout::determineFilePath(get_called_class());
    }


    /**
     * Gets the relative path to the module the controller lives in, or sprout itself
     *
     * @return string 'sprout' or 'modules/AwesomeModule'
     */
    public function getModulePath()
    {
        $path = self::getAbsModulePath();
        if (!$path) throw new Exception("Where am I?");

        $path = strtr($path, [
            DOCROOT => '',
            APPPATH => 'sprout/',
        ]);

        $parts = explode('/', $path);
        if (count($parts) < 2) throw new Exception("Where am I?");
        if ($parts[0] == 'sprout') return 'sprout';
        if ($parts[0] != 'modules') throw new Exception("Where am I?");
        return implode('/', array_slice($parts, 0, 2));
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