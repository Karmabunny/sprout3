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
* Returns data from a database table.
*
* This is basically than ColModifierLookupArray(Pdb::lookup),
* but it doesn't call the Pdb::lookup until right before it's needed,
* so if the itemlist doesn't get used, we aren't wasting a db call.
**/
class ColModifierLookupTable extends ColModifier
{
    private $data;
    private $table;

    public function __construct($table)
    {
        $this->table = $table;
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
        if ($val == '') return '';

        if (! $this->data) {
            $this->data = Pdb::lookup($this->table);
        }

        return @$this->data[$val];
    }

}
