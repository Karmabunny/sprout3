<?php
/*
 * Copyright (C) 2026 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Events;

use karmabunny\kb\Event;

/**
 * An event triggered at the very start of the application.
 *
 * This is after the output bufferring is active and before routes are loaded.
 */
class BootstrapEvent extends Event
{
    /**
     * The route table.
     *
     * @var array
     */
    public $routes = [];
}
