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

/**
 *
 */
abstract class Module implements ModuleInterface
{

    /**
     *
     * @return void
     */
    public function loadSprout(): void
    {
    }


    /**
     *
     * @return void
     */
    public function loadAdmin(): void
    {
    }


    /**
     *
     * @return string
     */
    public function getName(): string
    {
        $name = strtr(static::class, '\\', '/');
        $name = basename($name);
        $name = str_replace('Module', '', $name);
        return $name;
    }


    /**
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = Sprout::determineFilePath(static::class);
        return dirname($path);
    }


    /**
     *
     * @param string $package
     * @return string
     */
    protected static function getInstalledVersion(string $package): string
    {
        $version = InstalledVersions::getPrettyVersion($package) ?? 'dev';
        $reference = InstalledVersions::getReference($package) ?? 'unknown';

        return sprintf('%s - #%.7s (%s)', $version, $reference, $package);
    }
}
