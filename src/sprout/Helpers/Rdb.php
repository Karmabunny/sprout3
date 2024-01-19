<?php
/*
 * Copyright (C) 2020 Karmabunny Pty Ltd.
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

use karmabunny\rdb\Rdb as RealRdb;
use karmabunny\rdb\RdbConfig;
use karmabunny\rdb\StaticRdb;
use Kohana;

/**
 * Redis wrapper + utilities.
 */
class Rdb extends StaticRdb
{

    /** @inheritdoc */
    public static function getConfig(): RdbConfig
    {
        $config = Kohana::config('redis.default');
        return new RdbConfig($config);
    }


    /** @inheritdoc */
    public static function getInstance(): RealRdb
    {
        // TODO Remove this. This is fixed in v1.20.10.
        static $rdb;
        $rdb = $rdb ?? RealRdb::create(self::getConfig());
        return $rdb;
    }
}
