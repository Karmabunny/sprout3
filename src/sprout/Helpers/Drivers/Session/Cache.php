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
namespace Sprout\Helpers\Drivers\Session;

use Kohana;
use Kohana_Exception;

use Sprout\Helpers\Drivers\Cache AS CacheDriver;
use Sprout\Helpers\Drivers\SessionDriver;
use Sprout\Helpers\Encrypt;
use Sprout\Helpers\Session;


/**
 * Session cache driver.
 *
 * Cache library config goes in the session.storage config entry:
 * $config['storage'] = array(
 *     'driver' => 'apc',
 *     'requests' => 10000
 * );
 * Lifetime does not need to be set as it is
 * overridden by the session expiration setting.
 */
class Cache implements SessionDriver
{

    protected $cache;
    protected $encrypt;

    public function __construct()
    {
        // Load Encrypt library
        if (Kohana::config('session.encryption'))
        {
            $this->encrypt = new Encrypt;
        }

        Kohana::log('debug', 'Session Cache Driver Initialized');
    }

    public function open($path, $name)
    {
        $config = Kohana::config('session.storage');

        if (empty($config))
        {
            // Load the default group
            $config = Kohana::config('cache.default');
        }
        elseif (is_string($config))
        {
            $name = $config;

            // Test the config group name
            if (($config = Kohana::config('cache.'.$config)) === NULL)
                throw new Kohana_Exception('cache.undefined_group', $name);
        }

        $config['lifetime'] = (Kohana::config('session.expiration') == 0) ? 86400 : Kohana::config('session.expiration');
        $this->cache = new CacheDriver($config);

        return is_object($this->cache);
    }

    public function close()
    {
        return TRUE;
    }

    public function read($id)
    {
        $id = 'session_'.$id;
        if ($data = $this->cache->get($id))
        {
            return Kohana::config('session.encryption') ? $this->encrypt->decode($data) : $data;
        }

        // Return value must be string, NOT a boolean
        return '';
    }

    public function write($id, $data)
    {
        $id = 'session_'.$id;
        $data = Kohana::config('session.encryption') ? $this->encrypt->encode($data) : $data;

        return $this->cache->set($id, $data);
    }

    public function destroy($id)
    {
        $id = 'session_'.$id;
        return $this->cache->delete($id);
    }

    public function regenerate()
    {
        session_regenerate_id(TRUE);

        // Return new session id
        return session_id();
    }

    public function gc($maxlifetime)
    {
        // Just return, caches are automatically cleaned up
        return TRUE;
    }

} // End Session Cache Driver
