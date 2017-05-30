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

namespace Sprout\Helpers\DisplayConditions\Platform;

use Sprout\Helpers\DisplayConditions\DisplayConditionInteger;
use Sprout\Helpers\UserAgent;


/**
 * Display condition for browser version - 3, 4, 5, etc
 * Test is integerised, so therefore only checks the major number
 * Only makes sense when a conditon for the browser itself is also selected
 */
class BrowserVersion extends DisplayConditionInteger
{

    /**
     * Return the current value of the variable
     * This is compared against the params returned by {@see DisplayCondition::getParamValues}
     *
     * @param array $env Environment, such as page id etc
     * @return string
     */
    protected function getCurrentValue(array $env)
    {
        $info = UserAgent::getInfo();
        return @$info['browser_version'];
    }

}
