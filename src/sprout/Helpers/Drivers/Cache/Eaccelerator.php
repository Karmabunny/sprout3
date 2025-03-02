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
 * Eaccelerator-based Cache driver.
 */
class Eaccelerator implements CacheDriver
{

    public function __construct()
    {
        if ( ! extension_loaded('eaccelerator'))
            throw new Kohana_Exception('cache.extension_not_loaded', 'eaccelerator');
    }

    public function get($id)
    {
        return eaccelerator_get($id);
    }

    public function find($tag)
    {
        Kohana::log('error', 'tags are unsupported by the eAccelerator driver');

        return array();
    }

    public function set($id, $data, ?array $tags = NULL, $lifetime)
    {
        if ( ! empty($tags))
        {
            Kohana::log('error', 'tags are unsupported by the eAccelerator driver');
        }

        return eaccelerator_put($id, $data, $lifetime);
    }

    public function delete($id, $tag = FALSE)
    {
        if ($tag === TRUE)
        {
            Kohana::log('error', 'tags are unsupported by the eAccelerator driver');
            return FALSE;
        }
        elseif ($id === TRUE)
        {
            return eaccelerator_clean();
        }
        else
        {
            return eaccelerator_rm($id);
        }
    }

    public function deleteExpired()
    {
        eaccelerator_gc();

        return TRUE;
    }

} // End Cache eAccelerator Driver
