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

namespace Sprout\Helpers\DisplayConditions;


/**
 * Display condition for integers
 */
abstract class DisplayConditionInteger extends DisplayCondition
{

    /**
     * Return the list of operators for the condition value
     * Common values would be 'equals', 'not equals', 'less than', 'contains', etc
     *
     * @return array Key is internal value (e.g. '=') and value is the label (e.g. 'Equals')
     */
    public function getOperators()
    {
        return [
            '==' => 'Equals',
            '!=' => 'Not equals',
            '>' => 'Greater than',
            '>=' => 'Greater than or equal',
            '<' => 'Less than',
            '<=' => 'Less than or equal',
        ];
    }


    /**
     * Return the type of parameter for UI display
     * Only two types are supported - 'text' or 'dropdown'
     *
     * @return string
     */
    public function getParamType()
    {
        return 'text';
    }


    /**
     * Does a given condition match?
     *
     * Calls {@see DisplayConditionEnum::getCurrentValue} to get the current value
     * And then does the comparison against $val based on $op
     *
     * @param array $env Environment, such as page id etc
     * @param string $op string One of the keys from the array returned by {@see DisplayCondition::getOperators}
     * @param string $val string Entered value OR one of the keys from {@see DisplayCondition::getParamValues}
     * @return bool True of condition matches, false if it does not
     */
    public function match(array $env, $op, $val)
    {
        $val = (int) $val;
        $curr_val = (int) $this->getCurrentValue($env);
        switch ($op) {
            case '==':
                return ($curr_val == $val);
            case '!=':
                return ($curr_val != $val);
            case '>':
                return ($curr_val > $val);
            case '>=':
                return ($curr_val >= $val);
            case '<':
                return ($curr_val < $val);
            case '<=':
                return ($curr_val <= $val);
            default:
                return false;
        }
    }


    /**
     * Return the current value of the variable
     * This is compared against the params returned by {@see DisplayCondition::getParamValues}
     *
     * @param array $env Environment, such as page id etc
     * @return string
     */
    abstract protected function getCurrentValue(array $env);

}
