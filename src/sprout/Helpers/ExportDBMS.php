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
 * @package Sprout\Helpers
 */
interface ExportDBMS
{
    /**
     * Return the header for the export.
     *
     * @return string The header.
     */
    public function hdr();

    /**
     * Return a query to drop this table
     *
     * @param ExportTableSQL $table_def The table definition.
     * @return string The query to drop the table.
     */
    public function drop($table_def);


    /**
     * Return a query to create the table
     *
     * @param ExportTableSQL $table_def The table definition.
     * @return string The query to create the table.
     */
    public function structure($table_def);


    /**
     * Create an INSERT query
     *
     * @param ExportTableSQL $table_def The table definition.
     * @param array $row The row data.
     * @return string The query to insert the row.
     */
    public function insert($table_def, $row);


    /**
     * Create an UPDATE query
     *
     * @param ExportTableSQL $table_def The table definition.
     * @param string[] $pk_names The primary key column names.
     * @param array $row The row data.
     * @return string The query to update the row.
     */
    public function update($table_def, $pk_names, $row);


    /**
     * Create an INSERT...UPDATE query
     *
     * @param ExportTableSQL $table_def The table definition.
     * @param string[] $pk_names The primary key column names.
     * @param array $row The row data.
     * @return string The query to insert the row.
     */
    public function insertUpdate($table_def, $pk_names, $row);

}

