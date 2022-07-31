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

namespace Sprout\Services;

/**
 * The base service interface.
 *
 * All services are configurable.
 *
 * @package Sprout\Services
 */
interface ServiceInterface
{

    /**
     * Configure the service.
     *
     * @param array $config
     * @return void
     */
    public static function configure(array $config);
}
