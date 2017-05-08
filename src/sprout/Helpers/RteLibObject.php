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
* Returned by RteLibrary classes to represent a "object" (e.g. files) in the browse structure
**/
class RteLibObject
{
    private $name;
    private $label;
    private $attrs;
    private $props;


    public function __construct($name, $label, $attrs, $props)
    {
        $this->name = $name;
        $this->label = $label;
        $this->attrs = $attrs;
        $this->props = $props;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getAttrs()
    {
        return $this->attrs;
    }

    public function getProps()
    {
        return $this->props;
    }

    public function getIconClass()
    {
        return !empty($this->props['icon']) ? $this->props['icon'] : 'object';
    }
}
