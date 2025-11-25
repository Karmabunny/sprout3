<?php
/*
 * Copyright (C) 2025 Karmabunny Pty Ltd.
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

use karmabunny\interfaces\MutexInterface;
use karmabunny\pdb\PdbMutex;
use karmabunny\rdb\RdbMutex;
use Kohana;
use Kohana_Exception;

/**
 * Mutex helper.
 */
class Mutex
{

    /**
     * Create a new mutex.
     *
     * @param string $name mutex name
     * @param string $group config group
     * @return MutexInterface
     * @throws Kohana_Exception
     */
    public static function create(string $name, string $group = 'default'): MutexInterface
    {
        $config = Kohana::config('mutex.' . $group);

        if ($config['driver'] === 'pdb') {
            $pdb = Pdb::getInstance();
            $mutex = new PdbMutex($pdb, $name);

        } else if ($config['driver'] === 'redis') {
            $rdb = Rdb::getInstance();
            $mutex = new RdbMutex($rdb, $name);

        } else {
            throw new Kohana_Exception('Unknown mutex driver: ' . $config['driver']);
        }

        foreach ($config['config'] as $key => $value) {
            if (property_exists($mutex, $key)) {
                $mutex->$key = $value;
            }
        }

        return $mutex;
    }
}
