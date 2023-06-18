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
 *
 */
abstract class Module implements ModuleInterface
{

    /** @inheritdoc */
    public static function getName(): string
    {
        $name = strtr(static::class, '\\', '/');
        $name = basename($name);
        $name = str_replace('Module', '', $name);
        return $name;
    }


    /** @inheritdoc */
    public function loadSprout(): void
    {
        $path = $this->getPath() . '/sprout_load.php';

        if (is_readable($path)) {
            require_once $path;
        }
    }


    /** @inheritdoc */
    public function loadAdmin(): void
    {
        $path = $this->getPath() . '/admin_load.php';

        if (is_readable($path)) {
            require_once $path;
        }
    }


    /** @inheritdoc */
    public function getPath(): string
    {
        $path = Sprout::determineFilePath(static::class);
        return dirname($path);
    }
}
