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


/**
 * Base module class.
 *
 * For built-in (classic) modules, the migration is relatively simple:
 *
 * ```
 * class HomePageModule extends Module
 * {
 *     uses ModuleSiteTrait;
 * }
 * ```
 *
 * - anchors the module path to the class directory
 * - loads `sprout_load.php` and `admin_load.php` files (if present, co-located in the directory)
 * - module name is derived from the class, e.g. `'HomePage'`.
 */
abstract class Module implements ModuleInterface
{

    /** @var bool[] */
    protected $loaded = [];


    /** @inheritdoc */
    public static function getName(): string
    {
        $name = strtr(static::class, '\\', '/');
        $name = basename($name);
        $name = str_replace('Module', '', $name);
        return $name;
    }


    /** @inheritdoc */
    public function isLoaded(string $type = 'sprout'): bool
    {
        return $this->loaded[$type] ?? false;
    }


    /** @inheritdoc */
    public function loadSprout(): void
    {
        if ($this->isLoaded('sprout')) return;

        $path = $this->getPath() . '/sprout_load.php';

        if (is_readable($path)) {
            require_once $path;
        }

        $this->loaded['sprout'] = true;
    }


    /** @inheritdoc */
    public function loadAdmin(): void
    {
        if ($this->isLoaded('admin')) return;

        $path = $this->getPath() . '/admin_load.php';

        if (is_readable($path)) {
            require_once $path;
        }

        $this->loaded['admin'] = true;
    }


    /** @inheritdoc */
    public function getPath(): string
    {
        $path = Sprout::determineFilePath(static::class);
        return dirname($path);
    }
}
