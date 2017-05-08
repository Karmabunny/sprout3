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


/** Useful methods for debugging */
class Debug
{

    /**
     * Wraps a value in <pre> tags and calls print_r to show all of its data.
     *
     * N.B. Boolean and null values are displayed as strings instead of merely
     * returning an empty <pre>.
     *
     * @param mixed $val
     * @return string HTML
     */
    static function pre($val)
    {
        if (is_bool($val)) {
            $val = '<i>' . ($val? 'true': 'false') . '</i>';
        } else if ($val === null) {
            $val = '<i>null</i>';
        } else {
            $val = Enc::html(print_r($val, true));
        }
        return '<pre>' . $val . '</pre>';
    }


    /**
     * Determines the type of a variable.
     *
     * For objects, the class name is returned. For other variable types,
     * the type and possibly some extra info about its value is returned.
     *
     * @param mixed $val
     * @return string Plain text
     */
    static function type($val)
    {
        if (is_object($val)) {
            return get_class($val);
        } else if (is_array($val)) {
            $out = '[';
            $num = 0;
            foreach ($val as $key => $item) {
                if (++$num != 1) $out .= ', ';

                // Array keys can only be ints or strings
                // See http://php.net/manual/en/language.types.array.php
                if (is_int($key)) {
                    $out .= $key;
                } else {
                    $out .= "'" . addslashes($key) . "'";
                }
                $out .= ': ';
                if (is_array($item)) {
                    $out .= 'Array';
                } else {
                    $out .= self::type($item);
                }
            }
            $out .= ']';
        } else if (is_string($val)) {
            $out = gettype($val) . ': ' . str_replace("\0", "\\0", $val);
        } else {
            $out = gettype($val);
            if (is_bool($val)) {
                $out .= ': ' . ($val? 'true': 'false');
            } else if (!is_null($val)) {
                $out .= ': ' . $val;
            }
        }
        return $out;
    }


    /**
     * Converts a DB result set or similar multi-dimensional array into HTML for a table
     * @param array|PDOStatement $data If a PDOStatement, the cursor will automatically be closed after rendering
     * @return string
     */
    static function table($data)
    {
        if (!is_array($data) and !($data instanceof PDOStatement)) {
            throw new InvalidArgumentException('Must be array or PDOStatement');
        }

        if (is_array($data) and count($data) == 0) {
            return '<p>No data</p>';
        } else if ($data instanceof PDOStatement and $data->rowCount() == 0) {
            return '<p>No data</p>';
        }

        $out = "<table>\n";

        $first_row = true;
        foreach ($data as $row) {
            if ($first_row) {
                $first_row = false;
                $out .= "<tr>";
                foreach ($row as $col => $val) {
                    $out .= '<th>' . Enc::html($col) . '</th>';
                }
                $out .= "</tr>\n";
            }

            $out .= "<tr>";
            foreach ($row as $val) {
                if ($val === null) {
                    $out .= '<td><i>null</i></td>';
                    continue;
                }
                $out .= '<td>' . Enc::html($val) . '</td>';
            }
            $out .= "</tr>\n";
        }

        $out .= "</table>\n";

        if ($data instanceof PDOStatement) {
            $data->closeCursor();
        }

        return $out;
    }
}
