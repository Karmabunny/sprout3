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

use Sprout\Helpers\DisplayConditions\DisplayConditionEnum;
use Sprout\Helpers\UserAgent;


/**
 * Display condtion for browser - firefox, chrome, safari, etc
 */
class BrowserName extends DisplayConditionEnum
{

    /**
     * Return the available values for dropdowns
     *
     * @return array Key is internal value (e.g. 'desktop') and value is the label (e.g. 'Desktop')
     */
    public function getParamValues()
    {
        $opts = [
            'Chrome',
            'Edge',
            'Firefox',
            'Internet Explorer',
            'Opera',
            'Safari',
        ];
        return array_combine($opts, $opts);
    }


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
        return @$info['browser_name'];
    }

}
