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
 * Exporter for SQLite databases.
 *
 * @package Sprout\Helpers
 */
class ExportDBMS_SQLite implements ExportDBMS
{

    /** @inheritdoc */
    public function hdr()
    {
        return "--\n"
            . "-- Sprout3 Database Dump\n"
            . "-- Export date:    " . date('Y-m-d H:i:s') . "\n"
            . "-- Export format:  SQLite\n"
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
    * Return a query to create the table
    **/
    public function structure($table_def)
    {
        $q = "SHOW COLUMNS FROM `{$table_def->name}`";
        $res = Pdb::query($q, [], 'arr-num');

        // Build the column bits
        $autoinc = false;
        $cols = array();
        foreach ($res as $row) {
            $c = $row[0] . ' ';

            // SQLite is quite stict about autoinc types
            if ($row[5] == 'auto_increment') {
                $c .= 'INTEGER PRIMARY KEY AUTOINCREMENT';
                $cols[] = $c;
                $autoinc = true;
                continue;
            }

            $c .= $this->mapDatatype($row[1]);

            if ($row[2] == 'NO') {
                $c .= ' NOT NULL';
            }

            if ($row[4]) {
                $c .= ' DEFAULT ' . "'" . str_replace("'", "''", $row[4]) . "'";
            }

            $cols[] = $c;
        }

        // If it's not an autoinc, we need to manually specify the PK
        if (! $autoinc) {
            $pk_cols = array();
            foreach ($res as $row) {
                if ($row[3] == 'PRI') $pk_cols[] = $row[0];
            }

            $cols[] = 'PRIMARY KEY(' . implode(',', $pk_cols) . ')';
        }

        // Join up the create table statement
        $sql = "CREATE TABLE {$table_def->name} (\n\t" . implode(",\n\t", $cols) . "\n);\n";


        // Grab the indexes for the 'create index' clauses
        $q = "SHOW INDEX FROM `{$table_def->name}`";
        $res = Pdb::query($q, [], 'arr-num');

        // Iterate indexes and build a temp array
        $indexes = array();
        foreach ($res as $row) {
            if ($row[2] == 'PRIMARY') continue;

            $indexes[$row[2]]['type'] = ($row[1] == 1 ? 'INDEX' : 'UNIQUE INDEX');
            $indexes[$row[2]]['cols'][] = $row[4];
        }

        // Create index SQL statements
        foreach ($indexes as $name => $def) {
            $sql .= 'CREATE ' . $def['type'] . ' ' . $table_def->name . '_' . $name . ' ON ' . $table_def->name;
            $sql .= '(' . implode(',', $def['cols']) . ");\n";
        }

        return $sql;

    }


    /**
    * Map a MySQL data type to a SQLite data type
    **/
    private function mapDatatype($mysql_type)
    {
        $mysql_type = strtolower($mysql_type);

        if (preg_match('/(int|bit|bool)/', $mysql_type)) return 'INTEGER';
        if (preg_match('/(varchar|char|text|enum|set)/', $mysql_type)) return 'TEXT';
        if (preg_match('/(float|double|decimal|dec)/', $mysql_type)) return 'REAL';
        if (preg_match('/(date|time|year)/', $mysql_type)) return 'TEXT';
        if (preg_match('/(blob|binary)/', $mysql_type)) return 'BLOB';

        return 'TEXT';
    }


    /**
    * Create an INSERT query
    **/
    public function insert($table_def, $row)
    {
        $str = "INSERT INTO `{$table_def->name}` (";
        $j = 0;
        foreach ($row as $key => $val) {
            if ($j++ > 0) $str .= ',';
            $str .= $key;
        }
        $str .= ") VALUES (";
        $j = 0;
        foreach ($row as $key => $val) {
            if ($j++ > 0) $str .= ',';
            if ($val === null) {
                $str .= 'NULL';
            } else {
                $str .= "'" . str_replace("'", "''", $val) . "'";
            }
        }
        $str .= ");\n";

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
        $str = '';

        $j = 0;
        foreach ($row as $key => $val) {
            if ($j++ > 0) $str .= $sep;
            if ($val === null) {
                $val = 'NULL';
            } else {
                $val = "'" . str_replace("'", "''", $val) . "'";
            }
            $str .= "`{$key}` = " . $val;
        }

        return $str;
    }

}

