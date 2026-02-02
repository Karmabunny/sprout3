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

use Kohana;
use Kohana_Exception;

use Sprout\Helpers\Drivers\CacheDriver;


/**
 * Provides a driver-based interface for finding, creating, and deleting cached
 * resources. Caches are identified by a unique string. Tagging of caches is
 * also supported, and caches can be found and deleted by id or tag.
 */
class Cache
{

    protected static $instances = array();

    // For garbage collection
    protected static $loaded;

    // Configuration
    protected $config;

    // Driver object
    protected $driver;

    /**
     * Returns a singleton instance of Cache.
     *
     * @param   string|false $config Configuration
     * @return  Cache
     */
    public static function & instance($config = FALSE)
    {
        if ( ! isset(Cache::$instances[$config]))
        {
            // Create a new instance
            Cache::$instances[$config] = new Cache($config);
        }

        return Cache::$instances[$config];
    }

    /**
     * Loads the configured driver and validates it.
     *
     * @param   array|string|false $config Custom configuration or config group name
     * @return  void
     */
    public function __construct($config = FALSE)
    {
        if (is_string($config))
        {
            $name = $config;

            // Test the config group name
            if (($config = Kohana::config('cache.'.$config)) === NULL)
                throw new Kohana_Exception('cache.undefined_group', $name);
        }

        if (is_array($config))
        {
            // Append the default configuration options
            $config += Kohana::config('cache.default');
        }
        else
        {
            // Load the default group
            $config = Kohana::config('cache.default');
        }

        // Cache the config in the object
        $this->config = $config;

        // Set driver name
        $driver = 'Sprout\\Helpers\\Drivers\\Cache\\' . ucfirst($this->config['driver']);

        // Load the driver
        if ( ! class_exists($driver))
            throw new Kohana_Exception('core.driver_not_found', $this->config['driver'], get_class($this));

        // Initialize the driver
        $this->driver = new $driver($this->config['params']);

        // Validate the driver
        if ( ! ($this->driver instanceof CacheDriver))
            throw new Kohana_Exception('core.driver_implements', $this->config['driver'], get_class($this), 'CacheDriver');

        if (Cache::$loaded !== TRUE)
        {
            $this->config['requests'] = (int) $this->config['requests'];

            if ($this->config['requests'] > 0 AND mt_rand(1, $this->config['requests']) === 1)
            {
                // Do garbage collection
                $this->driver->deleteExpired();
            }

            // Cache has been loaded once
            Cache::$loaded = TRUE;
        }
    }

    /**
     * Fetches a cache by id. NULL is returned when a cache item is not found.
     *
     * @param   string $id Cache id
     * @return  mixed   cached data or NULL
     */
    public function get($id)
    {
        // Sanitize the ID
        $id = $this->sanitizeId($id);

        return $this->driver->get($id);
    }

    /**
     * Fetches all of the caches for a given tag. An empty array will be
     * returned when no matching caches are found.
     *
     * @param   string $tag Cache tag
     * @return  array   all cache items matching the tag
     */
    public function find($tag)
    {
        return $this->driver->find($tag);
    }

    /**
     * Set a cache item by id. Tags may also be added and a custom lifetime
     * can be set. Non-string data is automatically serialized.
     *
     * @param   string $id Unique cache id
     * @param   mixed $data Data to cache
     * @param   array|string|null $tags Tags for this item
     * @param   int|null $lifetime Number of seconds until the cache expires
     * @return  boolean
     */
    function set($id, $data, $tags = NULL, $lifetime = NULL)
    {
        if (is_resource($data))
            throw new Kohana_Exception('cache.resources');

        // Sanitize the ID
        $id = $this->sanitizeId($id);

        if ($lifetime === NULL)
        {
            // Get the default lifetime
            $lifetime = $this->config['lifetime'];
        }

        return $this->driver->set($id, $data, (array) $tags, $lifetime);
    }

    /**
     * Delete a cache item by id.
     *
     * @param   string $id Cache id
     * @return  boolean
     */
    public function delete($id)
    {
        // Sanitize the ID
        $id = $this->sanitizeId($id);

        return $this->driver->delete($id);
    }

    /**
     * Delete all cache items with a given tag.
     *
     * @param   string $tag Cache tag name
     * @return  boolean
     */
    public function deleteTag($tag)
    {
        return $this->driver->delete($tag, TRUE);
    }

    /**
     * Delete ALL cache items items.
     *
     * @return  boolean
     */
    public function deleteAll()
    {
        return $this->driver->delete(TRUE);
    }

    /**
     * Replaces troublesome characters with underscores.
     *
     * @param   string $id Cache id
     * @return  string
     */
    protected function sanitizeId($id)
    {
        // Change slashes and spaces to underscores
        return str_replace(array('/', '\\', ' '), '_', $id);
    }

} // End Cache
