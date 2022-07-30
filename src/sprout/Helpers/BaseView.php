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
namespace Sprout\Helpers;

use Exception;
use Kohana;
use Kohana_Exception;


/**
 * Loads and displays Kohana view files.
 */
abstract class BaseView
{

    protected static $EXTENSION = '.html';

    // The view file name and type
    protected $kohana_filename = FALSE;

    // View variable storage
    protected $kohana_local_data = array();
    protected static $kohana_global_data = array();

    /**
     * Attempts to load a view and pre-load view data.
     *
     * @throws Kohana_Exception if the requested view cannot be found
     * @param string $name view name
     * @param array $data pre-load data
     */
    public function __construct($name, array $data = [])
    {
        $this->setFilename($name);

        // Preload data using array_merge, to allow user extensions
        $this->kohana_local_data = array_merge($this->kohana_local_data, $data);
    }

    /**
     * Magic method access to test for view property
     *
     * @param   string   View property to test for
     * @return  boolean
     */
    public function __isset($key = NULL)
    {
        return $this->isPropertySet($key);
    }

    /**
     * Sets the view filename.
     *
     * @chainable
     * @param   string  view filename
     * @param   string  view file type
     * @return  object
     */
    public function setFilename($name)
    {
        $this->kohana_filename = Skin::findTemplate($name, static::$EXTENSION);
        return $this;
    }

    /**
     * Sets a view variable.
     *
     * @param   string|array  name of variable or an array of variables
     * @param   mixed         value when using a named variable
     * @return  object
     */
    public function set($name, $value = NULL)
    {
        if (is_array($name))
        {
            foreach ($name as $key => $value)
            {
                $this->__set($key, $value);
            }
        }
        else
        {
            $this->__set($name, $value);
        }

        return $this;
    }

    /**
     * Checks for a property existence in the view locally or globally. Unlike the built in __isset(),
     * this method can take an array of properties to test simultaneously.
     *
     * @param string $key property name to test for
     * @param array $key array of property names to test for
     * @return boolean property test result
     * @return array associative array of keys and boolean test result
     */
    public function isPropertySet( $key = FALSE )
    {
        // Setup result;
        $result = FALSE;

        // If key is an array
        if (is_array($key))
        {
            // Set the result to an array
            $result = array();

            // Foreach key
            foreach ($key as $property)
            {
                // Set the result to an associative array
                $result[$property] = (array_key_exists($property, $this->kohana_local_data) OR array_key_exists($property, self::$kohana_global_data)) ? TRUE : FALSE;
            }
        }
        else
        {
            // Otherwise just check one property
            $result = (array_key_exists($key, $this->kohana_local_data) OR array_key_exists($key, self::$kohana_global_data)) ? TRUE : FALSE;
        }

        // Return the result
        return $result;
    }

    /**
     * Sets a bound variable by reference.
     *
     * @param   string   name of variable
     * @param   mixed    variable to assign by reference
     * @return  object
     */
    public function bind($name, & $var)
    {
        $this->kohana_local_data[$name] =& $var;

        return $this;
    }

    /**
     * Sets a view global variable.
     *
     * @param   string|array  name of variable or an array of variables
     * @param   mixed         value when using a named variable
     * @return  void
     */
    public static function setGlobal($name, $value = NULL)
    {
        if (is_array($name))
        {
            foreach ($name as $key => $value)
            {
                self::$kohana_global_data[$key] = $value;
            }
        }
        else
        {
            self::$kohana_global_data[$name] = $value;
        }
    }

    /**
     * Magically sets a view variable.
     *
     * @param   string   variable key
     * @param   string   variable value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->kohana_local_data[$key] = $value;
    }

    /**
     * Magically gets a view variable.
     *
     * @param  string  variable key
     * @return mixed   variable value if the key is found
     * @return void    if the key is not found
     */
    public function &__get($key)
    {
        if (isset($this->kohana_local_data[$key]))
            return $this->kohana_local_data[$key];

        if (isset(self::$kohana_global_data[$key]))
            return self::$kohana_global_data[$key];

        if (isset($this->$key))
            return $this->$key;

        $default = null;
        return $default;
    }

    /**
     * Magically converts view object to string.
     *
     * @return  string
     */
    public function __toString()
    {
        try
        {
            return $this->render();
        }
        catch (Exception $e)
        {
            // Display the exception using its internal __toString method
            return (string) $e;
        }
    }

    /**
     * Renders a view.
     *
     * @param   boolean   set to TRUE to echo the output instead of returning it
     * @param   callback  special renderer to pass the output through
     * @return  string    if print is FALSE
     * @return  void      if print is TRUE
     */
    public abstract function render($print = FALSE, $renderer = FALSE);


    /**
     * Shorthand load and render a view.
     *
     * @param string $name
     * @param null|array $data
     * @return string
     * @throws Exception
     */
    public static function include(string $name, ?array $data = []): string
    {
        $view = new static($name, $data);
        return $view->render();
    }


    /**
     * Create a view as appropriate for the 'sprout.skin_views_type' config.
     *
     * This is important when rendering something from a skin. Because newer
     * skins will be Twig, whereas older skins will be PHP. Modules that render
     * out to a skin will want to use this so that they remain compatible with
     * either skin format.
     *
     * Beware though, internal sprout/ or modules/ templates will typically be
     * PHP _or_ Twig, not both. In this case, use `new TwigView()`
     * or `new PhpView()` appropriately.
     *
     * @param string $name
     * @param array $data
     * @return BaseView
     */
    public static function create(string $name, $data = [])
    {
        $type = strtolower(trim(Kohana::config('sprout.skin_views_type') ?? 'php'));

        switch ($type) {
            case 'php':
            default:
                return new PhpView($name, $data);

            case 'twig':
                return new TwigView($name, $data);
        }
    }

} // End View
