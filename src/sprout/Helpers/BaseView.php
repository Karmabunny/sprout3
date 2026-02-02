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
use Sprout\Exceptions\FileMissingException;

/**
 * Loads and displays Kohana view files.
 */
abstract class BaseView
{

    public static $DEBUG_COMMENT = !IN_PRODUCTION;

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
     * @param string $name The view filename
     *
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
     * @param   string|array $name name of variable or an array of variables
     * @param   mixed $value value when using a named variable
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
     * @param string|array $key property name to test for or array of property names to test for
     *
     * @return boolean|array  property test result or associative array of keys and boolean test result
     */
    public function isPropertySet($key = FALSE)
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
     * @param string $name name of variable
     * @param mixed $var variable to assign by reference
     * @return object
     */
    public function bind($name, &$var)
    {
        $this->kohana_local_data[$name] =& $var;

        return $this;
    }

    /**
     * Sets a view global variable.
     *
     * @param string|array $name name of variable or an array of variables
     * @param mixed $value value when using a named variable
     *
     * @return void
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
     * @param string $key variable key
     * @param string $value variable value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->kohana_local_data[$key] = $value;
    }

    /**
     * Magically gets a view variable.
     *
     * @param string $key variable key
     *
     * @return mixed variable value if the key is found or void if the key is not found
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
     * A debug comment indicates where this view is being loaded.
     *
     * This is injected into the rendered output. It's only enabled given the
     * static `DEBUG_COMMENT` property - default matches `IN_PRODUCTION`.
     *
     * This only appears in HTML/XML output.
     *
     * @return string
     */
    public function getDebugComment(): string
    {
        if (!static::$DEBUG_COMMENT) return '';

        $filename = str_replace(DOCROOT, '', $this->kohana_filename);
        $headers = headers_list();

        foreach ($headers as $header) {
            if (preg_match('/content-type:.*(html|xml)/i', $header)) {
                return "<!-- {$filename} -->";
            }
        }

        return '';
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
            Kohana::logException($e, false);
            // Display the exception using its internal __toString method
            return (string) $e;
        }
    }

    /**
     * Renders a view.
     *
     * @param boolean $print set to TRUE to echo the output instead of returning it
     * @param string|false $renderer Special renderer callback to pass the output through
     *
     * @return  string|null Return the view as a string, or null if print is FALSE
     */
    public abstract function render($print = FALSE, $renderer = FALSE);


    /**
     * Shorthand load and render a view.
     *
     * @param string $name
     * @param null|array $data
     *
     * @return string
     *
     * @throws Exception
     */
    public static function include(string $name, ?array $data = []): string
    {
        /** @phpstan-ignore-next-line */
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
     *
     * @return BaseView
     *
     * @throws Exception An invalid template path
     */
    public static function create(string $name, $data = []): BaseView
    {
        $view = self::getSkinType();
        return new $view($name, $data);
    }


    /**
     * Same as create() but enforces that only skin templates will be loaded.
     *
     * This can be particularly useful because most (all) modules and core
     * templates do not offer both a PHP and a Twig template, so performing
     * skin-dynamic loading is somewhat pointless.
     *
     * Use this if you're unsure. Perhaps the name 'skin()' will make it's
     * behaviour more obvious.
     *
     * @param string $name
     * @param array $data
     *
     * @return BaseView
     *
     * @throws Exception An invalid skin path
     */
    public static function skin(string $name, $data = []): BaseView
    {
        if (!preg_match('!^skin!', $name)) {
            throw new Exception('Not a skin template: ' . $name);
        }

        return self::create($name, $data);
    }


    /**
     * Does this template exist?
     *
     * This abides by dynamic-skin loading rules for different view types. So
     * if the skin is set to 'twig' it'll search for `'skin/etc/view.twig'`.
     *
     * @param string $name
     * @return bool
     *
     * @throws Kohana_Exception
     * @throws Exception
     */
    public static function exists(string $name): bool
    {
        try {
            $view = self::getSkinType();
            Skin::findTemplate($name, $view::$EXTENSION);
            return true;

        } catch (FileMissingException $ex) {
            return false;
        }
    }

    /**
     * The View class to use for the current skin.
     *
     * @return string a class name
     */
    public static function getSkinType(): string
    {
        $type = Kohana::config('sprout.skin_views_type') ?? 'php';
        $type = strtolower(trim($type));

        switch ($type) {
            case 'php':
            default:
                return PhpView::class;

            case 'twig':
                return TwigView::class;
        }
    }

} // End View
