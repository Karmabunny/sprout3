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
 */
namespace Sprout\Helpers\Drivers\Cache;

use karmabunny\rdb\Rdb as RdbClient;

use Sprout\Helpers\Drivers\CacheDriver;
use Sprout\Helpers\Rdb as RdbHelper;

/**
 * Redis-based Cache driver.
 *
 * ```
 * // Use the default redis connection (from config/redis).
 * $config['default'] = [
 *     'driver' => 'rdb',
 *     'params' => 'cache:',
 *     'lifetime' => 1800,
 *     'requests' => 1000,
 * ];
 *
 * // Specify a new redis connection.
 * $config['default'] = [
 *     'driver' => 'rdb',
 *     'params' => [
 *         'host' => 'localhost',
 *         'prefix' => 'cache:',
 *         'adapter' => 'predis',
 *     ],
 *     'lifetime' => 1800,
 *     'requests' => 1000,
 * ];
 * ```
 */
class Rdb implements CacheDriver
{

    /** @var RdbClient */
    public RdbClient $rdb;

    /** @var string */
    protected string $prefix = '';


    /**
     * Create a Redis cache driver.
     *
     * - host: localhost
     * - prefix: 'cache:'
     * - adapter: predis (default) | php-redis | credis
     *
     * @see karmabunny\rdb\RdbConfig
     * @param array|string $config key prefix (re-use global redis) or config array (new connection)
     */
    public function __construct(array|string $config)
    {
        if (is_string($config)) {
            $this->rdb = RdbHelper::getInstance();
            $this->prefix = $config;
        }
        else {
            $this->rdb = RdbClient::create($config);
        }
    }


    /** @inheritdoc */
    public function set($id, $data, ?array $tags = NULL, $lifetime = 0)
    {
        $ok = $this->rdb->setJson($this->prefix . 'data:' . $id, $data, $lifetime);

        if ($tags !== null) {
            foreach ($tags as $tag) {
                $this->rdb->sAdd($this->prefix . 'tags:' . $tag, $id);
            }
        }

        return $ok > 0;
    }


    /** @inheritdoc */
    public function find($tag)
    {
        $ids = $this->rdb->sScan($this->prefix . 'tags:' . $tag);

        $data = [];
        $dead = [];

        foreach ($ids as $id) {
            $item = $this->rdb->getJson($this->prefix . 'data:' . $id);

            if ($item === null) {
                $dead[] = $id;
            } else {
                $data[$id] = $item;
            }
        }

        if ($dead) {
            $this->rdb->sRem($this->prefix . 'tags:' . $tag, $dead);
        }

        return $data;
    }


    /** @inheritdoc */
    public function get($id)
    {
        return $this->rdb->getJson($this->prefix . 'data:' . $id);
    }


    /** @inheritdoc */
    public function delete($id, $tag = FALSE)
    {
        // Delete all items.
        if ($id === true) {
            $this->rdb->flushPrefix();
            return true;
        }

        // Delete tags.
        if ($tag) {
            $ids = $this->rdb->sScan($this->prefix . 'tags:' . $id);
            $ids = $this->rdb->prefix($this->prefix . 'data:', $ids);
            $ok = $this->rdb->del($ids, $this->prefix . 'tags:' . $id);
            return $ok > 0;
        }

        // Delete a single item.
        $ok = $this->rdb->del($this->prefix . 'data:' . $id);
        return $ok > 0;
    }


    /** @inheritdoc */
    public function deleteExpired()
    {
    }

}
