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
* Converts a MySQL date into something friendlier. Works for DATE, TIME, DATETIME AND [BIG]INT.
*
* The default output format is d/m/Y, but can be changed in the constructor.
* Format strings are anything supported by the PHP function date().
**/
class ColModifierDate extends SortedColModifier
{
    private $format;

    /**
     * @param string $format The format (see PHP's date function, {@link http://php.net/manual/en/function.date.php})
     */
    public function __construct($format = 'd/m/Y')
    {
        $this->format = $format;
    }

    /**
    * Modify a column value
    * This value will be html/csv/etc encoded afterwards.
    *
    * @param string $val The incoming value
    * @param string $field_name The name of the field being modified
    * @return string The modified value
    **/
    public function modify($val, $field_name)
    {
        if ($val == '' or $val == '0000-00-00') return '';

        // Unix timestamp stored in an INT or BIGINT column
        if (preg_match('/^[0-9\.]+$/', $val)) return date($this->format, $val);

        // DATE/TIME/DATETIME
        return date($this->format, strtotime($val));
    }

}


