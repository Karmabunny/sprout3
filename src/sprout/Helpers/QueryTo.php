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
     *
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @param array $headings Headings to use on the first row of the CSV; as column_name => heading.
     *        The column name itself will be used if a specific heading isn't requested.
     * @return false|string The CSV file or false on error
     * @throws InvalidArgumentException
     */
    static public function csv($result, array $modifiers = [], array $headings = [])
    {
        $stream = @fopen('php://temp', 'r+');
        if (!$stream) return false;

        $ok = self::csvFile($result, $stream, $modifiers, $headings);
        if (!$ok) return false;

        $ok = rewind($stream);
        if (!$ok) return false;

        $out = @stream_get_contents($stream);
        if (!$out) return false;

        @fclose($stream);
        return $out;
    }


    /**
     * Exports a database query result as a CSV file.
     *
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param resource $stream file handle
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @param array $headings Headings to use on the first row of the CSV; as column_name => heading.
     *        The column name itself will be used if a specific heading isn't requested.
     * @return false|string The CSV file or false on error
     * @throws InvalidArgumentException
     */
    static public function csvFile($result, $stream, array $modifiers = [], array $headings = [])
    {
        $is_pdo = ($result instanceof PDOStatement);
        if (!$is_pdo and !is_iterable($result)) {
            throw new InvalidArgumentException('$result must be a PDOStatement or an iterable');
        }

        // Header
        $row = [];

        if ($is_pdo) {
            if ($result->rowCount() == 0) {
                $result->closeCursor();
                return false;
            }

            for ($i = 0; $i < $result->columnCount(); ++$i) {
                $col = $result->getColumnMeta($i);
                $name = $col['name'];
                if (@$modifiers[$name] === false) continue;

                $row[] = isset($headings[$name]) ? $headings[$name] : $name;
            }

        } else {
            $first_row = Sprout::iterableFirstValue($result);

            if ($first_row === null) {
                return false;
            }

            foreach ($first_row as $name => $junk) {
                if (@$modifiers[$name] === false) continue;
                $row[] = isset($headings[$name]) ? $headings[$name] : $name;
            }
        }

        fputcsv($stream, $row);

        // Data
        foreach ($result as $row) {
            $out_row = [];

            foreach ($row as $key => $val) {
                if (@$modifiers[$key] === false) continue;

                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key);
                }

                $out_row[] = $val;
            }

            fputcsv($stream, $out_row);
        }

        if ($is_pdo) $result->closeCursor();

        return true;
    }


    /**
     * Exports a database query result as an XML file.
     *
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @return false|string The XML file or false on error
     * @throws InvalidArgumentException
     */
    static public function xml($result, array $modifiers = [])
    {
        $stream = @fopen('php://temp', 'r+');
        if (!$stream) return false;

        $ok = self::xmlFile($result, $stream, $modifiers);
        if (!$ok) return $ok;

        $ok = rewind($stream);
        if (!$ok) return false;

        $out = @stream_get_contents($stream);
        if (!$out) return false;

        @fclose($stream);
        return $out;
    }


    /**
     * Exports a database query result as an XML file.
     *
     * @param PDOStatement|iterable $result Result set. N.B. the cursor on this statement WILL BE CLOSED by this function.
     * @param resources $stream a file handle
     * @param array $modifiers ColModifier objects to apply result set before exporting their values,
     *        as column_name => ColModifier instance
     * @return bool false on error
     * @throws InvalidArgumentException
     */
    static public function xmlFile($result, $stream, array $modifiers = [])
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

        $count = 0;

        // Data
        foreach ($result as $row) {
            if ($count == 0) {
                fputs($stream, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . PHP_EOL);
                fputs($stream, '<data>' . PHP_EOL);
            }

            $count++;
            fputs($stream, "    <record");

            if ($row['id']) {
                $row['id'] = Enc::xml($row['id']);
                fputs($stream, " id=\"{$row['id']}\"");
                unset($row['id']);
            }

            fputs($stream, ">" . PHP_EOL);

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

                fputs($stream, "        <{$key}>{$val}</{$key}>" . PHP_EOL);
            }

            fputs($stream, "    </record>" . PHP_EOL);
        }

        if ($count > 0) {
            fputs($stream, '</data>' . PHP_EOL);
        }

        if ($is_pdo) $result->closeCursor();

        return $count > 0;
    }
}


