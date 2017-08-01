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

namespace Sprout\Helpers\DisplayConditions\Session;

use Sprout\Helpers\DisplayConditions\DisplayConditionInteger;
use Sprout\Helpers\SessionStats;


/**
 * Display condtion for time on site in minutes
 */
class TimeOnSite extends DisplayConditionInteger
{

    /**
     * Return the current value of the variable
     *
     * @param array $env Environment, such as page id etc
     * @return string
     */
    protected function getCurrentValue(array $env)
    {
        $time = SessionStats::timeOnSite();
        return $time->i;
    }

}
