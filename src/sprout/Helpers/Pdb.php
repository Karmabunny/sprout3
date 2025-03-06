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
use karmabunny\pdb\PdbConfig;
use karmabunny\pdb\Pdb as RealPdb;
use Kohana;

/**
 * Class for doing database queries via PDO (PDO Database => Pdb)
 */
class Pdb extends StaticPdb
{
    protected static $prefix = 'sprout_';

    /** @inheritdoc */
    public static function getConfig(?string $name = null): PdbConfig
    {
        $name = $name ?? 'default';
        $config = Kohana::config('database.' . $name);

        $conf = $config['connection'];
        $conf['type'] = str_replace('mysqli', 'mysql', $conf['type']);
        $conf['character_set'] = $config['character_set'];
        $conf['prefix'] = $config['prefix'] ?? self::$prefix;
        $conf['hacks'] = $config['hacks'] ?? [];
        $conf['session'] = $config['session'] ?? [];

        if (!isset($conf['transaction_mode'])) {
            $conf['transaction_mode'] = 0
                | PdbConfig::TX_STRICT_COMMIT
                | PdbConfig::TX_STRICT_ROLLBACK
                | PdbConfig::TX_ENABLE_NESTED;
        }

        return new PdbConfig($conf);
    }


    /** @inheritdoc */
    public static function getInstance(string $type = 'RW'): RealPdb
    {
        $pdb = parent::getInstance($type);

        $enabled = Profiling::isEnabled();

        if ($enabled) {
            $pdb->setProfiler(function($position, $query) {
                if ($position == 'begin') {
                    Profiling::begin($query, self::class);
                }
                else {
                    Profiling::end($query, self::class);
                }
            });
        }

        return $pdb;
    }
}
