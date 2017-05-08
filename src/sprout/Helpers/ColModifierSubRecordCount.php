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
 * Counts records in a subtable.
 *
 * E.g. count the records in a gallery_images table associated with records in a galleries table
 * The database query isn't done until the first record lookup
 */
class ColModifierSubRecordCount extends ColModifier
{
    private $data = null;
    private $table;
    private $column;


    /**
     * @param string $table The table that contains the records
     * @param string $column The column in $table that links to the main table, which by convention is the table's
     *        singular name followed by _id, e.g. if $table is 'galleries' then $column is usually 'gallery_id'
     */
    public function __construct($table, $column)
    {
        Pdb::validateIdentifier($table);
        Pdb::validateIdentifier($column);
        $this->table = $table;
        $this->column = $column;
    }


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
        if ($val == '') return '';

        if ($this->data === null) {
            $q = "SELECT `{$this->column}`, COUNT(*) FROM ~{$this->table} GROUP BY `{$this->column}`";
            $this->data = Pdb::q($q, [], 'map');
        }

        return (int) @$this->data[$val];
    }

}
