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

use karmabunny\kb\Config as BaseConfig;

/**
 * Configuration helper.
 */
class Config extends BaseConfig
{

    /**
     * Search paths for config files.
     *
     * @var string[]
     */
    public static array $paths = [
        'sprout' => APPPATH . 'config/',
        'docroot' => DOCROOT . 'config/',
    ];


    /** @inheritdoc */
    public function getPaths(): array
    {
        return self::$paths;
    }


    /**
     * Load a config file, no overrides, no caching.
     *
     * @param string $file
     * @param string $name
     * @return array
     */
    public static function include(string $file, string $name = 'config')
    {
        return self::load($file, $name);
    }


    /**
     * Returns the value of a key, defined by a 'dot-noted' string, from an array.
     *
     * @param   array   $array  array to search
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @return  string|array|null
     * @deprecated use Config::query() instead
     */
    public static function keyString(array $array, string $keys)
    {
        return self::query($array, $keys);
    }


    /**
     * Sets values in an array by using a 'dot-noted' string.
     *
     * @param   array   $array  array to set keys in (reference)
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @param   mixed   $value   fill value for the key
     * @return  void
     * @deprecated use Config::querySet() instead
     */
    public static function keyStringSet(array &$array, string $keys, $value)
    {
        self::querySet($array, $keys, $value);
    }
}
