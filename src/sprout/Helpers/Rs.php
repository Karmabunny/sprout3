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

use InvalidArgumentException;

use PDOStatement;


/**
 * Functions to hack database query result sets
 */
class Rs
{

    /**
     * Groups rows in a resultset (e.g. by category, date, ...)
     *
     * @param array|PDOStatement $rs Resultset, which MUST have an id column
     * @param string $group_id The column with the field to group by,
     *        e.g. cat_id
     * @param string $group_fields Extra data for the group segments. The keys
     *        are the fields for each group, and the values are the
     *        corresponding field names in the result set. For example,
     *        ['name' => 'cat_name', 'slug' => 'cat_slug']
     * @return array Grouped rows. The key is the group id, and the value is
     *         an array with key 'rows', and a matching key for each of the
     *         specified $group_fields
     */
    public static function groupByField($rs, $group_id, array $group_fields = array()) {
        if (!is_array($rs) and !($rs instanceof PDOStatement)) {
            throw new InvalidArgumentException('$rs must be array or PDOStatement');
        }

        $grouped = array();
        foreach ($rs as $row) {
            if (!isset($row['id'])) {
                throw new InvalidArgumentException('$rs must have an id column');
            }
            $key = $row[$group_id];
            if (!isset($grouped[$key])) {
                $grouped[$key] = array('rows' => array());
                foreach ($group_fields as $field => $alias) {
                    if ($field == 'rows') continue;
                    $grouped[$key][$field] = $row[$alias];
                }
            }
            foreach ($group_fields as $field => $alias) {
                unset($row[$alias]);
            }
            $grouped[$key]['rows'][$row['id']] = $row;
        }
        return $grouped;
    }
}
