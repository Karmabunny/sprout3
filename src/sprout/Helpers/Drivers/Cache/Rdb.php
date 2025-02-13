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


/**
 * Redis-based Cache driver.
 */
class Rdb implements CacheDriver
{

    /** @var RdbClient */
    public $rdb;


    /**
     * Create a Redis cache driver.
     *
     * - host: localhost
     * - prefix: 'cache:'
     * - adapter: predis (default) | php-redis | credis
     *
     * @see karmabunny\rdb\RdbConfig
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->rdb = RdbClient::create($config);
    }


    /** @inheritdoc */
    public function set($id, $data, array $tags = NULL, $lifetime = 0)
    {
        $ok = $this->rdb->setJson('data:' . $id, $data, $lifetime * 1000);

        if ($tags !== null) {
            foreach ($tags as $tag) {
                $this->rdb->sAdd('tags:' . $tag, $id);
            }
        }

        return $ok > 0;
    }


    /** @inheritdoc */
    public function find($tag)
    {
        $ids = $this->rdb->sScan('tags:' . $tag);

        $data = [];
        $dead = [];

        foreach ($ids as $id) {
            $item = $this->rdb->getJson('data:' . $id);

            if ($item === null) {
                $dead[] = $id;
            } else {
                $data[$id] = $item;
            }
        }

        if ($dead) {
            $this->rdb->sRem('tags:' . $tag, $dead);
        }

        return $data;
    }


    /** @inheritdoc */
    public function get($id)
    {
        return $this->rdb->getJson('data:' . $id);
    }


    /** @inheritdoc */
    public function delete($id, $tag = FALSE)
    {
        if ($id === true) {
            $ok = $this->rdb->flushPrefix();

        } else if ($tag) {
            $ids = $this->rdb->sScan('tags:' . $id);
            $ids = $this->rdb->prefix('data:', $ids);
            $ok = $this->rdb->del($ids, 'tags:' . $id);

        } else {
            $ok = $this->rdb->del('data:' . $id);
        }

        return $ok > 0;
    }


    /** @inheritdoc */
    public function deleteExpired()
    {
    }

}
