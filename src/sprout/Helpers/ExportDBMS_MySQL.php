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

class ExportDBMS_MySQL
{

    public function hdr()
    {
        return "--\n"
            . "-- Sprout3 Database Dump\n"
            . "-- Export date:    " . date('Y-m-d H:i:s') . "\n"
            . "-- Export format:  MySQL\n"
            . "--\n";
    }


    /**
    * Return a query to drop this table
    **/
    public function drop($table_def)
    {
        return "DROP TABLE IF EXISTS `{$table_def->name}`;\n";
    }


    /**
     * Remove CONSTRAINT clauses from a CREATE TABLE sql query
     *
     * @param string $create_sql SQL query for CREATE TABLE which may contain CONSTRAINT clauses
     * @return string SQL query which does not contain CONSTRAINT clauses
     */
    private static function delConstraintsCreate($create_sql)
    {
        $open_paren = strpos($create_sql, '(') + 1;
        $close_paren = strrpos($create_sql, ')');

        if ($open_paren === false or $close_paren === false) {
            return $create_sql;
        }

        // Split up the column/index/constraint definitions
        $column_defs = substr($create_sql, $open_paren, $close_paren - $open_paren);
        $column_defs = explode(',', $column_defs);

        // Remove all constraint definitions
        foreach ($column_defs as $index => $def) {
            if (strpos($def, 'CONSTRAINT') !== false) {
                unset($column_defs[$index]);
            }
        }

        // Put the query back together again
        $new_create_sql = substr($create_sql, 0, $open_paren);
        $new_create_sql .= implode(',', $column_defs);
        $new_create_sql .= PHP_EOL . substr($create_sql, $close_paren);

        return $new_create_sql;
    }


    /**
    * Return a query to create the table
    **/
    public function structure($table_def)
    {
        $q = "SHOW CREATE TABLE `{$table_def->name}`";
        $res = Pdb::query($q, [], 'row-num');
        $create_sql = $res[1];

        // Importing will fail trying to create tables if tables are out-of-order
        // Remove the constraints so that the CREATEs succeed, and then a dbsync can bring them back
        $create_sql = self::delConstraintsCreate($create_sql);

        return $create_sql . ";\n";
    }


    /**
    * Create an INSERT query
    **/
    public function insert($table_def, $row)
    {
        $str = "INSERT INTO `{$table_def->name}` SET ";
        $str .= $this->createKvpString($row);
        $str .= ";\n";

        return $str;
    }


    /**
    * Create an UPDATE query
    **/
    public function update($table_def, $pk_names, $row)
    {
        $pk = array();
        foreach ($pk_names as $col) {
            $pk[$col] = $row[$col];
        }

        $row = array_diff_key($row, $pk);

        $str = "UPDATE `{$table_def->name}` SET ";
        $str .= $this->createKvpString($row);
        $str .= " WHERE ";
        $str .= $this->createKvpString($pk, ' AND ');
        $str .= ";\n";

        return $str;
    }


    /**
    * Create an INSERT...UPDATE query
    **/
    public function insertUpdate($table_def, $pk_names, $row)
    {
        $row_no_pk = $row;
        foreach ($pk_names as $name) {
            unset ($row_no_pk[$name]);
        }

        if (count($row_no_pk) == 0) {
            $str = "INSERT IGNORE INTO `{$table_def->name}` SET ";
            $str .= $this->createKvpString($row);
            $str .= ";\n";
        } else {
            $str = "INSERT INTO `{$table_def->name}` SET ";
            $str .= $this->createKvpString($row);
            $str .= " ON DUPLICATE KEY UPDATE ";
            $str .= $this->createKvpString($row_no_pk);
            $str .= ";\n";
        }

        return $str;
    }


    /**
    * Creates a key-value-pair string for use in a sql query
    **/
    private function createKvpString($row, $sep = ', ')
    {
        static $conn;
        $str = '';

        if (!$conn) $conn = Pdb::getConnection();

        $j = 0;
        foreach ($row as $key => $val) {
            if ($j++ > 0) $str .= $sep;
            if ($val === null) {
                $val = 'NULL';
            } else {
                $val = $conn->quote($val);
            }
            $str .= "`{$key}` = " . $val;
        }

        return $str;
    }

}

