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
* No description yet.
**/
class QueryTo
{

    /**
     * Exports a database query result as a CSV file.
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @param array $headings Headings to use on the first row of the CSV; as column_name => heading.
     *        The column name itself will be used if a specific heading isn't requested.
     * @return string The CSV file
     * @return bool False on error
     */
    static public function csv($result, array $modifiers = [], array $headings = [])
    {
        $is_pdo = ($result instanceof PDOStatement);
        if (!$is_pdo and !is_iterable($result)) {
            throw new InvalidArgumentException('$result must be a PDOStatement or an iterable');
        }

        $out = '';

        // Header
        $j = 0;
        if ($is_pdo) {
            if ($result->rowCount() == 0) {
                $result->closeCursor();
                return false;
            }

            for ($i = 0; $i < $result->columnCount(); ++$i) {
                $col = $result->getColumnMeta($i);
                $name = $col['name'];
                if (@$modifiers[$name] === false) continue;
                if ($j++ > 0) $out .= ',';
                $out .= '"' . (isset($headings[$name]) ? $headings[$name] : $name) . '"';
            }
        } else {
            $first_row = Sprout::iterableFirstValue($result);

            if ($first_row === null) {
                return false;
            }

            foreach ($first_row as $name => $junk) {
                if (@$modifiers[$name] === false) continue;
                if ($j++ > 0) $out .= ',';
                $out .= '"' . (isset($headings[$name]) ? $headings[$name] : $name) . '"';
            }
        }
        $out .= "\n";

        // Data
        foreach ($result as $row) {
            $j = 0;
            foreach ($row as $key => $val) {
                if (@$modifiers[$key] === false) continue;
                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key);
                }

                $val = str_replace('"', '""', $val);

                if ($j++ > 0) $out .= ',';
                $out .= '"' . $val . '"';
            }
            $out .= "\n";
        }

        if ($is_pdo) $result->closeCursor();

        return $out;
    }

    /**
     * Exports a database query result as an XML file.
     *
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @return string The XML file
     * @return bool False on error
     */
    static public function xml($result, array $modifiers = [])
    {
        $is_pdo = ($result instanceof PDOStatement);
        if (!$is_pdo and !is_iterable($result)) {
            throw new InvalidArgumentException('$result must be a PDOStatement or an iterable');
        }

        if ($is_pdo) {
            if ($result->rowCount() == 0) {
                $result->closeCursor();
                return false;
            }
        }

        $out = "<data>\n";
        $count = 0;

        // Data
        foreach ($result as $row) {
            $count++;
            $out .= "    <record";

            if ($row['id']) {
                $row['id'] = Enc::xml($row['id']);
                $out .= " id=\"{$row['id']}\"";
                unset($row['id']);
            }

            $out .= ">\n";

            foreach ($row as $key => $val) {
                if (@$modifiers[$key] === false) continue;
                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key);
                }

                $key = preg_replace('/[^a-z0-9]_/', '', strtolower($key));

                $val = Enc::xml($val);
                $val = trim($val);

                if (strpos($val , "\n") !== false) {
                    $val = "\n" . $val . "\n        ";
                }

                $out .= "        <{$key}>{$val}</{$key}>\n";
            }

            $out .= "    </record>\n";
        }

        $out .= "</data>\n";

        if ($is_pdo) $result->closeCursor();

        if (empty($count)) {
            return false;
        }

        return $out;
    }
}


