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
 * Tries to extract a name from an action log entry to display with an ID
 */
class ColModifierActionLogData extends SortedColModifier
{

    /**
     * Modify a column value
     * This value will be html/csv/etc encoded afterwards.
     *
     * @param string $val The incoming value
     * @param string $field_name The name of the field being modified
     * @return string The modified value
     */
    public function modify($val, $field_name, $row)
    {
        $val = (int) $val;
        $q = "SELECT record_table, record_id, data FROM ~history_items WHERE id = ?";
        $row = Pdb::q($q, [$val], 'row');

        $out = $row['record_id'];
        $name = '';
        $data = json_decode($row['data'], true);
        if (!empty($data['name'])) $name = $data['name'];
        if ($row['record_table'] == 'files') $name = $data['filename'];
        if ($name) $out .= ": {$name}";
        return $out;
    }

}
