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

namespace Sprout\Events;

use karmabunny\kb\Event;

class BeforeActionEvent extends Event
{
    /**
     * Set this `true` to prevent the controller method from executing.
     *
     * It's advised that the handler actually does something - like print to
     * output or redirect.
     *
     * @var bool
     */
    public $cancelled = false;

    /**
     * Method name for the controller.
     *
     * @var string
     */
    public $method;

    /**
     * Arguments for the controller method.
     *
     * @var array
     */
    public $arguments;
}
