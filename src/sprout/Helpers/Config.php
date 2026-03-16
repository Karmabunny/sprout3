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

namespace Sprout\Helpers;

use ArrayObject;
use Exception;
use Kohana_Exception;

/**
 * Configuration helper.
 */
class Config
{

    // Configuration
    private static $configuration;

    // Include paths
    private static $include_paths;

    // Internal cache
    private static $internal_cache = [];

    // Cache enabled
    public static $cache_enabled = IN_PRODUCTION;


    /**
     * Get all include paths. APPPATH is the first path, followed by module
     * paths in the order they are configured.
     *
     * @param   bool   $process  re-process the include paths
     * @return  array
     */
    public static function includePaths($process = FALSE)
    {
        if ($process === TRUE)
        {
            self::$include_paths = array();

            // Sprout modules first
            foreach (Modules::getModules() as $module)
            {
                if ($path = str_replace('\\', '/', $module->getPath()))
                {
                    // Add a valid path
                    self::$include_paths[] = $path;
                }
            }

            // Add Sprout core next
            self::$include_paths[] = APPPATH;
        }

        return self::$include_paths;
    }


    /**
     * Get a config item or group.
     *
     * @param   string   $key       item name
     * @param   bool     $required  is the item required?
     * @return  mixed
     */
    public static function get($key, $required = TRUE)
    {
        if (self::$configuration === NULL)
        {
            // Load core configuration
            self::$configuration = array();
            self::$configuration['config'] = self::load('config');

            // Re-parse the include paths
            self::includePaths(TRUE);
        }

        // Get the group name from the key
        $group = explode('.', $key, 2);
        $group = $group[0];

        $configuration = self::$configuration;
        $sub_config = self::$configuration[$group] ?? null;

        if ($sub_config === null) {
            // Load the configuration group
            $sub_config = self::load($group, $required);
            $configuration[$group] = $sub_config;

            // Store it if we're happy about the subsites.
            if (
                $group !== 'sprout'
                or !empty(SubsiteSelector::$subsite_code)
            ) {
                self::$configuration[$group] = $sub_config;
            }
        }

        // Get the value of the key string
        $value = self::keyString($configuration, $key);

        return $value;
    }


    /**
     * Sets a configuration item, if allowed.
     *
     * @param   string   $key    config key string
     * @param   string   $value  config value
     * @return  bool
     */
    public static function set($key, $value)
    {
        // Do this to make sure that the config array is already loaded
        self::get($key);

        if (substr($key, 0, 7) === 'routes.')
        {
            // Routes cannot contain sub keys due to possible dots in regex
            $keys = explode('.', $key, 2);
        }
        else
        {
            // Convert dot-noted key string to an array
            $keys = explode('.', $key);
        }

        // Used for recursion
        $conf =& self::$configuration;
        $last = count($keys) - 1;

        foreach ($keys as $i => $k)
        {
            if ($i === $last)
            {
                $conf[$k] = $value;
            }
            else
            {
                $conf =& $conf[$k];
            }
        }

        return TRUE;
    }


    /**
     * Load a kohana config file.
     *
     * This assumes that the file will _declare_ an array called named
     * 'config' - or defined by the `$name` parameter.
     *
     * @param string $file absolute path to file
     * @param string $name variable name
     * @return array|null
     */
    public static function include(string $file, string $name = 'config')
    {
        static $__recurse;

        // Prevent infinite recursion.
        if ($file === $__recurse) {
            throw new Exception('Recursive config file inclusion: ' . basename($file, '.php'));
        }

        // TODO should we throw if the file doesn't exist?

        return (function($__file, $__name) use (&$__recurse) {
            try {
                $__recurse = $__file;
                include $__file;

                if (isset($$__name) and is_array($$__name)) {
                    return $$__name;
                }

                return null;
            } finally {
                $__recurse = null;
            }
        })($file, $name);
    }


    /**
     * Load a config file.
     *
     * @param   string   $name      config filename, without extension
     * @param   bool     $required  is the file required?
     * @return  array
     */
    public static function load($name, $required = TRUE)
    {
        if ($name === 'config')
        {
            // Load the application configuration file
            $config = self::include(APPPATH . 'config/config.php', 'config');

            if ( ! isset($config['site_domain']))
            {
                // Invalid config file
                die('Your Kohana application configuration file is not valid.');
            }

            return $config;
        }

        $is_sprout = $name === 'sprout';

        if (
            !$is_sprout
            and self::$cache_enabled
            and isset(self::$internal_cache['configuration'][$name])
        ) {
            return self::$internal_cache['configuration'][$name];
        }

        // Load matching configs
        $configuration = array();

        if ($files = self::findFile('config', $name, $required))
        {
            foreach ($files as $file)
            {
                $config = self::include($file, 'config');

                if (isset($config))
                {
                    // Merge in configuration
                    $configuration = array_merge($configuration, $config);
                }
            }
        }

        if (!$is_sprout) {
            self::$internal_cache['configuration'][$name] = $configuration;
        }

        return $configuration;
    }

    /**
     * Clears a config group from the cached configuration.
     *
     * @param   string  $group  config group
     * @return  void
     */
    public static function clear($group)
    {
        // Remove the group from config
        unset(self::$configuration[$group], self::$internal_cache['configuration'][$group]);
    }




    /**
     * Find a resource file in a given directory. Files will be located according
     * to the order of the include paths. Configuration and i18n files will be
     * returned in reverse order.
     *
     * @throws  Kohana_Exception  if file is required and not found
     * @param   string   $directory  directory to search in
     * @param   string   $filename   filename to look for (without extension)
     * @param   bool     $required   file required
     * @param   string|false   $ext        file extension
     * @return  array|string|false
     *    - array:   if the type is config, i18n or l10n
     *    - string:  if the file is found
     *    - false:   if the file is not found
     */
    public static function findFile($directory, $filename, $required = FALSE, $ext = FALSE)
    {
        // NOTE: This test MUST be not be a strict comparison (===), or empty
        // extensions will be allowed!
        if ($ext == '')
        {
            // Use the default extension
            $ext = '.php';
        }
        else
        {
            // Add a period before the extension
            $ext = '.'.$ext;
        }

        // Search path
        $search = $directory.'/'.$filename.$ext;
        $is_sprout = strpos($search, 'config/sprout') === 0;

        if (
            !$is_sprout
            and self::$cache_enabled
            and isset(self::$internal_cache['find_file_paths'][$search])
        ) {
            return self::$internal_cache['find_file_paths'][$search];
        }

        // Load include paths
        $paths = self::$include_paths;

        // Nothing found, yet
        $found = NULL;

        if ($directory === 'config')
        {
            array_unshift($paths, DOCROOT);
            array_unshift($paths, DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/');
        }
        else if ($directory === 'views')
        {
            array_unshift($paths, DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/');
        }

        if ($directory === 'config' OR $directory === 'i18n')
        {
            // Search in reverse, for merging
            $paths = array_reverse($paths);

            foreach ($paths as $path)
            {
                if (is_file($path.$search))
                {
                    // A matching file has been found
                    $found[] = $path.$search;
                }
            }
        }
        else
        {
            foreach ($paths as $path)
            {
                if (is_file($path.$search))
                {
                    // A matching file has been found
                    $found = $path.$search;

                    // Stop searching
                    break;
                }
            }
        }

        if ($found === NULL)
        {
            if ($required === TRUE)
            {
                // Directory i18n key
                $directory = 'core.'.Inflector::singular($directory);

                // If the file is required, throw an exception
                throw new Kohana_Exception('core.resource_not_found', I18n::lang($directory), $filename);
            }
            else
            {
                // Nothing was found, return FALSE
                $found = FALSE;
            }
        }

        if (!$is_sprout) {
            self::$internal_cache['find_file_paths'][$search] = $found;
        }

        return $found;
    }

    /**
     * Lists all files and directories in a resource path.
     *
     * @param   string   $directory  directory to search
     * @param   bool     $recursive  list all files to the maximum depth?
     * @param   string|false $path   full path to search (used for recursion, *never* set this manually)
     * @return  array  filenames and directories
     */
    public static function listFiles($directory, $recursive = FALSE, $path = FALSE)
    {
        $files = array();

        if ($path === FALSE)
        {
            $paths = array_reverse(self::includePaths());

            foreach ($paths as $path)
            {
                // Recursively get and merge all files
                $files = array_merge($files, self::listFiles($directory, $recursive, $path.$directory));
            }
        }
        else
        {
            $path = rtrim($path, '/').'/';

            if (is_readable($path))
            {
                $items = (array) glob($path.'*');

                if ( ! empty($items))
                {
                    foreach ($items as $index => $item)
                    {
                        $files[] = $item = str_replace('\\', '/', $item);

                        // Handle recursion
                        if (is_dir($item) AND $recursive == TRUE)
                        {
                            // Filename should only be the basename
                            $item = pathinfo($item, PATHINFO_BASENAME);

                            // Append sub-directory search
                            $files = array_merge($files, self::listFiles($directory, TRUE, $path.$item));
                        }
                    }
                }
            }
        }

        return $files;
    }


    /**
     * Returns the value of a key, defined by a 'dot-noted' string, from an array.
     *
     * @param   array   $array  array to search
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @return  string|array|null
     */
    public static function keyString($array, $keys)
    {
        if (empty($array))
            return NULL;

        // Prepare for loop
        $keys = explode('.', $keys);

        if (count($keys) == 2)
        {
            return @$array[$keys[0]][$keys[1]];
        }

        do
        {
            // Get the next key
            $key = array_shift($keys);

            if (isset($array[$key]))
            {
                if (is_array($array[$key]) AND ! empty($keys))
                {
                    // Dig down to prepare the next loop
                    $array = $array[$key];
                }
                else
                {
                    // Requested key was found
                    return $array[$key];
                }
            }
            else
            {
                // Requested key is not set
                break;
            }
        }
        // @phpstan-ignore-next-line: array_shift() will eventually empty the array.
        while ( ! empty($keys));

        return NULL;
    }

    /**
     * Sets values in an array by using a 'dot-noted' string.
     *
     * @param   array|object   $array  array to set keys in (reference)
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @param   mixed   $fill   fill value for the key
     * @return  void
     */
    public static function keyStringSet( & $array, $keys, $fill = NULL)
    {
        if (is_object($array) AND ($array instanceof ArrayObject))
        {
            // Copy the array
            $array_copy = $array->getArrayCopy();

            // Is an object
            $array_object = TRUE;
        }
        else
        {
            if ( ! is_array($array))
            {
                // Must always be an array
                $array = (array) $array;
            }

            // Copy is a reference to the array
            $array_copy =& $array;
        }

        if (empty($keys))
            return;

        // Create keys
        $keys = explode('.', $keys);

        // Create reference to the array
        $row =& $array_copy;

        for ($i = 0, $end = count($keys) - 1; $i <= $end; $i++)
        {
            // Get the current key
            $key = $keys[$i];

            if ( ! isset($row[$key]))
            {
                if (isset($keys[$i + 1]))
                {
                    // Make the value an array
                    $row[$key] = array();
                }
                else
                {
                    // Add the fill key
                    $row[$key] = $fill;
                }
            }
            elseif (isset($keys[$i + 1]))
            {
                // Make the value an array
                $row[$key] = (array) $row[$key];
            }

            // Go down a level, creating a new row reference
            $row =& $row[$key];
        }

        if (isset($array_object))
        {
            // Swap the array back in
            $array->exchangeArray($array_copy);
        }
    }
}
