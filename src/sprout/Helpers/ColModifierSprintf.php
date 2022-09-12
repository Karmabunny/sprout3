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
* Format something using printf syntax.
**/
class ColModifierSprintf extends SortedColModifier
{
    private $format;

    /**
     * @param string $format The printf format, see `sprintf` docs
     */
    public function __construct($format)
    {
        $this->format = $format;
    }

    /** @inheritdoc */
    public function modify($val, $field_name)
    {
        if (empty($val)) return '';
        return sprintf($this->format, $val);
    }

}


