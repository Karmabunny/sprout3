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
 * Interface for SproutCMS modules.
 *
 * @see Module
 */
interface ModuleInterface
{

    /**
     * Get the name of this module.
     *
     * This is used to identify the module everywhere.
     *
     * - view prefixes: `modules/{name}
     * - routing: `_media/{name}/...`
     * - needs: `Needs:fileGroup($name)`
     *
     * @return string
     */
    public static function getName(): string;


    /**
     * Is this module already loaded?
     *
     * @param string $type sprout|admin
     * @return bool
     */
    public function isLoaded(string $type = 'sprout'): bool;


    /**
     * Execute initial setup.
     *
     * This is loaded AFTER routing but BEFORE any controllers are created.
     *
     * This will not be executed more than once.
     *
     * @return void
     */
    public function loadSprout(): void;


    /**
     * Execute admin setup.
     *
     * These are loaded while constructing admin controllers.
     *
     * This will not be executed more than once.
     *
     * @return void
     */
    public function loadAdmin(): void;


    /**
     * Get the absolute path of this module.
     *
     * You can expect these files to be in this directory:
     *
     *  - db_struct.xml
     *  - media/
     *  - views/
     *
     * Classic modules will also have:
     * - sprout_load.php
     * - admin_load.php
     *
     * @return string
     */
    public function getPath(): string;


    /**
     * Get the version of this module.
     *
     * This is typically the composer package tag + commit hash but there's
     * no requirement for any particular format.
     *
     * In-built (classic) modules can use the `ModuleSiteTrait` to use the
     * root package version.
     *
     * @return string
     */
    public function getVersion(): string;
}
