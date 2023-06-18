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

use Composer\InstalledVersions;
use Exception;

/**
 *
 */
class ClassicModule extends Module
{

    /** @var string */
    public $name;

    /** @var string */
    public $path;


    /** @inheritdoc */
    public function loadSprout(): void
    {
        if (!is_dir($this->path)) {
            throw new Exception("Module '{$this->name}' does not exist: {$this->path}");
        }

        if (is_readable($this->path . '/sprout_load.php')) {
            require_once $this->path . '/sprout_load.php';
        }
    }


    /** @inheritdoc */
    public function loadAdmin(): void
    {
        if (is_readable($this->path . '/admin_load.php')) {
            require_once $this->path . '/admin_load.php';
        }
    }


    /** @inheritdoc */
    public function getName(): string
    {
        return $this->name;
    }


    /** @inheritdoc */
    public function getPath(): string
    {
        return $this->path;
    }


    /** @inheritdoc */
    public function getVersion(): string
    {
        $root = InstalledVersions::getRootPackage();
        return self::getInstalledVersion($root['name']);
    }
}
