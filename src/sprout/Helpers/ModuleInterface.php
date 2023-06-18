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
interface ModuleInterface
{

    /**
     *
     * @return string
     */
    public static function getName(): string;


    /**
     *
     * @return void
     */
    public function loadSprout(): void;


    /**
     *
     * @return void
     */
    public function loadAdmin(): void;


    /**
     *
     * @return string
     */
    public function getPath(): string;


    /**
     *
     * @return string
     */
    public function getVersion(): string;
}
