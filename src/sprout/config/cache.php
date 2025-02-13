<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

/**
 * @package  Cache
 *
 * Cache settings, defined as arrays, or "groups". If no group name is
 * used when loading the cache library, the group named "default" will be used.
 *
 * Each group can be used independently, and multiple groups can be used at once.
 *
 * Group Options:
 *  driver   - Cache backend driver. Kohana comes with file, database, and memcache drivers.
 *              > File cache is fast and reliable, but requires many filesystem lookups.
 *              > Database cache can be used to cache items remotely, but is slower.
 *              > Memcache is very high performance, but prevents cache tags from being used.
 *
 *  params   - Driver parameters, specific to each driver.
 *
 *  lifetime - Default lifetime of caches in seconds. By default caches are stored for
 *             thirty minutes. Specific lifetime can also be set when creating a new cache.
 *             Setting this to 0 will never automatically delete caches.
 *
 *  requests - Average number of cache requests that will processed before all expired
 *             caches are deleted. This is commonly referred to as "garbage collection".
 *             Setting this to 0 or a negative number will disable automatic garbage collection.
 */
$config['default'] = array
(
    'driver'   => 'file',
    'params'   => STORAGE_PATH . 'cache',
    'lifetime' => 1800,
    'requests' => 1000
);

$config['media'] = array
(
    'driver'   => 'file',
    'params'   => STORAGE_PATH . 'cache/media',
    'lifetime' => 900,
    'requests' => 500
);
