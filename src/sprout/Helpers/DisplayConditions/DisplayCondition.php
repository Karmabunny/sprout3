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
 * Base class for display conditions
 *
 * This is known as the Context Engine, and is a set of display rules which affect
 * which widgets (content blocks) are shown or hidden on the page
 */
abstract class DisplayCondition
{

    /**
     * Return the list of operators for the condition value
     * Common values would be 'equals', 'not equals', 'less than', 'contains', etc
     *
     * @return array Key is internal value (e.g. '=') and value is the label (e.g. 'Equals')
     */
    abstract public function getOperators();


    /**
     * Return the type of parameter for UI display
     * Only two types are supported - 'text' or 'dropdown'
     *
     * @return string
     */
    abstract public function getParamType();


    /**
     * Return the available values for dropdowns
     *
     * @return array Key is internal value (e.g. 'desktop') and value is the label (e.g. 'Desktop')
     */
    public function getParamValues()
    {
        return [];
    }

}