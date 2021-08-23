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
* Converts byte value into human readable sizes
**/
class ColModifierSize extends UnescapedColModifier
{
    /**
     * Modify a column value
     * This value will be html/csv/etc encoded afterwards.
     *
     * @param string $val The incoming value
     * @param string $field_name The name of the field being modified
     * @return string The modified value
     */
    public function modify($val, $field_name)
    {
        static $types = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $val = (int) $val;

        $type = 0;
        while ($val > 1024) {
            $val /= 1024;
            $type++;
            if ($type > 5) break;
        }

        return sprintf('%s&nbsp;%s', round($val, 1), $types[$type]);
    }
}
