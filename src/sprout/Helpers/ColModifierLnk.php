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
* Formats a Lnk field into something appealing
**/
class ColModifierLnk extends ColModifier
{

    public function __construct()
    {
    }

    /**
    * Modify a column value
    * This value will be html/csv/etc encoded afterwards.
    *
    * @param string $val The incoming value
    * @param string $field_name The name of the field being modified
    * @return string The modified value
    **/
    public function modify($val, $field_name, $row)
    {
        if ($val == '') return '';

        return 'Link to ' . Lnk::typename($val);
    }

}
