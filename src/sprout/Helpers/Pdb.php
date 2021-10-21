<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
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

use karmabunny\pdb\Compat\StaticPdb;
use karmabunny\pdb\Pdb as RealPdb;
use karmabunny\pdb\PdbConfig;
use Kohana;
use Kohana_Exception;
use PDO;

/**
 * Class for doing database queries via PDO (PDO Database => Pdb)
 */
class Pdb extends StaticPdb
{
    protected static $prefix = 'sprout_';

    protected static $connections = [];


    /** @inheritdoc */
    public static function getInstance(string $type = 'RW'): RealPdb
    {
        if (isset(self::$connections['override'])) {
            $name = 'override';
        }
        else if ($type == 'RO') {
            $name = 'read_only';
        }
        else {
            $name = 'default';
        }

        // A cached version.
        if ($pdb = self::$connections[$name] ?? null) {
            return $pdb;
        }

        // Start fresh.
        $config = self::getConfig($name);
        $pdb = RealPdb::create($config);

        $connections[$name] = $pdb;
        return $pdb;
    }


    /** @inheritdoc */
    public static function connect(string $name): PDO
    {
        $config = self::getConfig($name);
        return RealPdb::connect($config);
    }


    /**
     *
     * @param string $name
     * @return PdbConfig
     * @throws Kohana_Exception
     */
    protected static function getConfig(string $name): PdbConfig
    {
        $config = Kohana::config('database.' . $name);

        $conf = $config['connection'];
        $conf['type'] = str_replace('mysqli', 'mysql', $conf['type']);
        $conf['character_set'] = $config['character_set'];
        $conf['prefix'] = self::$prefix;

        return new PdbConfig($conf);
    }
}
