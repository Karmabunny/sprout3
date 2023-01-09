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
     * @return mixed
     */
    public function _run($method, $args)
    {
        // This is better than try-catch for 'bad method' exceptions. Where it
        // would also accidentally catch errors from deeper in the stack, this
        // method does not.
        if (!method_exists($this, $method)) {
            Event::run('system.404');
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
        $path = $this->getModulePath();

        if (preg_match('!^sprout/!', $path)) {
            return APPPATH;
        }

        return DOCROOT . $path;
    }


    /**
     * Gets the relative path to the module the controller lives in, or sprout itself
     *
     * @return string 'sprout' or 'modules/AwesomeModule'
     */
    public function getModulePath()
    {
        // __FILE__ doesn't work here. Gotta use late static bindings to
        // determine the calling class path.
        $path = Sprout::determineFilePath(static::class);

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