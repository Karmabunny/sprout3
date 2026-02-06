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
namespace Sprout\Helpers\Drivers\Cache;

use Kohana;
use Kohana_Exception;

use Sprout\Helpers\Drivers\CacheDriver;


/**
 * Memcache-based Cache driver.
 *
 * @deprecated SHOULD remove soon, we don't actively use memcache
 */
class Memcache implements CacheDriver
{

    const TAGS_KEY = 'memcache_tags_array';

    /** @var \Memcache */
    protected $backend;

    /** @var int|false */
    protected $flags;

    /** @var array */
    protected static $tags;

    // Have the tags been changed?
    protected static $tags_changed = FALSE;

    public function __construct()
    {
        if ( ! extension_loaded('memcache'))
            throw new Kohana_Exception('cache.extension_not_loaded', 'memcache');

        $this->backend = new \Memcache;
        $this->flags = Kohana::config('cache_memcache.compression') ? MEMCACHE_COMPRESSED : FALSE;

        $servers = Kohana::config('cache_memcache.servers');

        foreach ($servers as $server)
        {
            // Make sure all required keys are set
            $server += array('host' => '127.0.0.1', 'port' => 11211, 'persistent' => FALSE);

            // Add the server to the pool
            $this->backend->addServer($server['host'], $server['port'], (bool) $server['persistent'])
                or Kohana::log('error', 'Cache: Connection failed: '.$server['host']);
        }

        // Load tags
        $this->tags = $this->backend->get(self::TAGS_KEY);

        if ( ! is_array($this->tags))
        {
            // Create a new tags array
            $this->tags = array();

            // Tags have been created
            $this->tags_changed = TRUE;
        }
    }

    public function __destruct()
    {
        if ($this->tags_changed === TRUE)
        {
            // Save the tags
            $this->backend->set(self::TAGS_KEY, $this->tags, $this->flags, 0);

            // Tags are now unchanged
            $this->tags_changed = FALSE;
        }
    }

    public function find($tag)
    {
        if (isset($this->tags[$tag]) AND $results = $this->backend->get($this->tags[$tag]))
        {
                // Return all the found caches
                return $results;
        }
        else
        {
            // No matching tags
            return array();
        }
    }

    public function get($id)
    {
        return (($return = $this->backend->get($id)) === FALSE) ? NULL : $return;
    }

    public function set($id, $data, ?array $tags = NULL, $lifetime = 0)
    {
        if ( ! empty($tags))
        {
            // Tags will be changed
            $this->tags_changed = TRUE;

            foreach ($tags as $tag)
            {
                // Add the id to each tag
                $this->tags[$tag][$id] = $id;
            }
        }

        if ($lifetime !== 0)
        {
            // Memcache driver expects unix timestamp
            $lifetime += time();
        }

        // Set a new value
        return $this->backend->set($id, $data, $this->flags, $lifetime);
    }

    public function delete($id, $tag = FALSE)
    {
        // Tags will be changed
        $this->tags_changed = TRUE;

        if ($id === TRUE)
        {
            if ($status = $this->backend->flush())
            {
                // Remove all tags, all items have been deleted
                $this->tags = array();

                // We must sleep after flushing, or overwriting will not work!
                // @see http://php.net/manual/en/function.memcache-flush.php#81420
                sleep(1);
            }

            return $status;
        }
        elseif ($tag === TRUE)
        {
            if (isset($this->tags[$id]))
            {
                foreach ($this->tags[$id] as $_id)
                {
                    // Delete each id in the tag
                    $this->backend->delete($_id);
                }

                // Delete the tag
                unset($this->tags[$id]);
            }

            return TRUE;
        }
        else
        {
            foreach ($this->tags as $tag => $_ids)
            {
                if (isset($this->tags[$tag][$id]))
                {
                    // Remove the id from the tags
                    unset($this->tags[$tag][$id]);
                }
            }

            return $this->backend->delete($id);
        }
    }

    public function deleteExpired()
    {
        // Tags will be changed
        $this->tags_changed = TRUE;

        foreach ($this->tags as $tag => $_ids)
        {
            foreach ($_ids as $id)
            {
                if ( ! $this->backend->get($id))
                {
                    // This id has disappeared, delete it from the tags
                    unset($this->tags[$tag][$id]);
                }
            }

            if (empty($this->tags[$tag]))
            {
                // The tag no longer has any valid ids
                unset($this->tags[$tag]);
            }
        }

        // Memcache handles garbage collection internally
    }

} // End Cache Memcache Driver
