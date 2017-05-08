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

class Export
{
    private $tables = array();
    private $generated_files = array();
    private $filename_prefix = '';
    private $dbms = null;
    private $file_size = 0;
    private $file_idx = 0;
    private $hdr = '';

    public $split_table = false;
    public $split_size = 0;


    public function addTable(ExportTable $table)
    {
        $this->tables[] = $table;
    }

    public function getGeneratedFiles()
    {
        return $this->generated_files;
    }

    public function setFilenamePrefix($val)
    {
        $this->filename_prefix = $val;
    }

    public function setDbms($dbms)
    {
        $this->dbms = $dbms;
    }


    /**
    * Exports the specified tables to an SQL file
    * @tag maybe-error-not-mysql
    **/
    public function exportSql()
    {

        $this->hdr = $this->dbms->hdr();

        if (! $this->split_table) {
            $next_filename = $this->createNextFilename(null, '.sql');
            $curr_file = fopen($this->filename_prefix . $next_filename, 'w');
            fwrite($curr_file, $this->hdr);
            $this->generated_files[] = $next_filename;

            $this->file_size = 0;
            $this->file_idx = 1;
        }


        foreach ($this->tables as $table_def) {

            // If per-table splitting is on, open a new file
            if ($this->split_table) {
                $next_filename = $this->createNextFilename($table_def->name, '.sql');
                $curr_file = fopen($this->filename_prefix . $next_filename, 'w');
                fwrite($curr_file, $this->hdr);
                $this->generated_files[] = $next_filename;

                $this->file_size = 0;
                $this->file_idx = 1;
            }

            // If dropping is on, add the query
            if ($table_def->drop) {
                $str = $this->dbms->drop($table_def);

                $next_filename = $this->createNextFilename($table_def->name, '.sql');
                $curr_file = $this->sizeCheckWrite($curr_file, $str, $next_filename);
            }

            // If structure is on, add the query
            if ($table_def->structure) {
                $str = $this->dbms->structure($table_def);

                $next_filename = $this->createNextFilename($table_def->name, '.sql');
                $curr_file = $this->sizeCheckWrite($curr_file, $str, $next_filename);
            }

            // Dump data (CSV file)
            if ($table_def->data == ExportTableSQL::DATA_CSV) {
                $q = "SELECT * FROM `{$table_def->name}`";
                if ($table_def->where) $q .= " WHERE {$table_def->where}";

                $res = Pdb::query($q, [], 'pdo');
                $csv_data = QueryTo::csv($res);

                file_put_contents($this->filename_prefix . $table_def->name . '.csv', $csv_data);
                $this->generated_files[] = $table_def->name . '.csv';


            // Dump data (inserts, updates, insert..updates
            } else if ($table_def->data != ExportTableSQL::DATA_NONE) {
                $q = "SELECT * FROM `{$table_def->name}`";
                if ($table_def->where) $q .= " WHERE {$table_def->where}";

                $res = Pdb::query($q, [], 'pdo');

                $pk_names = $this->getPkColNames($table_def->name);

                foreach ($res as $row) {
                    switch ($table_def->data) {
                        case ExportTableSQL::DATA_INSERT:
                            // insert query
                            $str = $this->dbms->insert($table_def, $row);
                            break;

                        case ExportTableSQL::DATA_UPDATE:
                            // update query
                            $str = $this->dbms->update($table_def, $pk_names, $row);
                            break;

                        case ExportTableSQL::DATA_BOTH:
                            // insert...update
                            $str = $this->dbms->insertUpdate($table_def, $pk_names, $row);
                            break;

                    }

                    $next_filename = $this->createNextFilename($table_def->name, '.sql');
                    $curr_file = $this->sizeCheckWrite($curr_file, $str, $next_filename);
                }

                $res->closeCursor();
            }

            fwrite($curr_file, "\n");

            // If per-table splitting is on, open a new file
            if ($this->split_table) {
                fclose($curr_file);
            }
        }

        if (! $this->split_table) {
            fclose($curr_file);
        }
    }


    /**
    * Creates the filename which should be used for the next file if the current file is deemed to be too full
    **/
    private function createNextFilename($table_name, $ext)
    {
        return ($this->split_table ? $table_name : 'all') . '-' . $this->file_idx . $ext;
    }


    /**
    * Gets the column names for the primary key
    * @tag maybe-error-not-mysql
    **/
    private function getPkColNames($table)
    {
        $q = "SHOW COLUMNS FROM `{$table}`";
        $res = Pdb::query($q, [], 'pdo');

        $columns = array();
        foreach ($res as $row) {
            if ($row['Key'] == 'PRI') $columns[] = $row['Field'];
        }
        $res->closeCursor();

        return $columns;
    }


    /**
    * Writes data to a file, if allowed by the file size restrictions.
    * If not, creates a new file.
    **/
    private function sizeCheckWrite($handle, $str, $next_filename)
    {
        $this->file_size += strlen($str);

        if ($this->split_size != 0 and $this->file_size > $this->split_size) {
            fclose($handle);

            $this->file_size = strlen($str);
            $this->file_idx++;

            $handle = fopen($this->filename_prefix . $next_filename, 'w');
            fwrite($handle, $this->hdr);
            $this->generated_files[] = $next_filename;
        }

        fwrite($handle, $str);

        return $handle;
    }


    /**
    * Loads all of the generated export files into a ZIP file
    **/
    public function buildArchive($name)
    {
        $arch = new Archive('zip');

        foreach ($this->generated_files as $filename) {
            $arch->add($this->filename_prefix . $filename, $filename);
        }

        $arch->save($this->filename_prefix . $name);
        $this->generated_files = array($name);
    }


}


