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
use RuntimeException;

class Export
{

    /** @var ExportTableSQL[] */
    private $tables = array();

    /** @var string[] */
    private $generated_files = array();

    /** @var string */
    private $filename_prefix = '';

    /** @var ExportDBMS|null */
    private $dbms = null;

    /** @var int */
    private $file_size = 0;

    /** @var int */
    private $file_idx = 0;

    /** @var string */
    private $hdr = '';

    /** @var bool */
    public $split_table = false;

    /** @var int */
    public $split_size = 0;


    /**
     * Adds a table to the export.
     *
     * @param ExportTableSQL $table The table to add.
     * @return void
     */
    public function addTable(ExportTableSQL $table)
    {
        $this->tables[] = $table;
    }

    /**
     * Gets the generated files.
     *
     * @return array The generated files.
     */
    public function getGeneratedFiles()
    {
        return $this->generated_files;
    }

    /**
     * Sets the filename prefix for the export files.
     *
     * @param string $val The filename prefix.
     * @return void
     */
    public function setFilenamePrefix($val)
    {
        $this->filename_prefix = $val;
    }

    /**
     * Sets the DBMS for the export.
     *
     * @param ExportDBMS $dbms The DBMS to use.
     * @return void
     */
    public function setDbms($dbms)
    {
        $this->dbms = $dbms;
    }


    /**
     * Exports the specified tables to an SQL file
     * @tag maybe-error-not-mysql
     *
     * @return void
     * @throws InvalidArgumentException If the DBMS is not set.
     * @throws RuntimeException If the file handle cannot be opened for writing.
     */
    public function exportSql()
    {
        if ($this->dbms === null) {
            throw new InvalidArgumentException('DBMS not set');
        }

        $this->hdr = $this->dbms->hdr();

        $curr_file = false;

        if (! $this->split_table) {
            $next_filename = $this->createNextFilename(null, '.sql');
            $curr_file = fopen($this->filename_prefix . $next_filename, 'w');

            if ($curr_file !== false) {
                fwrite($curr_file, $this->hdr);
            }

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

            if ($curr_file === false) {
                throw new RuntimeException('Failed to open file for writing');
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

                $file = fopen($this->filename_prefix . $table_def->name . '.csv', 'w');

                QueryTo::csvFile($res, $file);

                fclose($file);

                $this->generated_files[] = $table_def->name . '.csv';


            // Dump data (inserts, updates, insert..updates
            } else if ($table_def->data != ExportTableSQL::DATA_NONE) {
                $q = "SELECT * FROM `{$table_def->name}`";
                if ($table_def->where) $q .= " WHERE {$table_def->where}";

                $res = Pdb::query($q, [], 'pdo');

                $pk_names = $this->getPkColNames($table_def->name);

                foreach ($res as $row) {
                    $str = '';
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

                    if ($str) {
                        $next_filename = $this->createNextFilename($table_def->name, '.sql');
                        $curr_file = $this->sizeCheckWrite($curr_file, $str, $next_filename);
                    }
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
     *
     * @param string|null $table_name The table name.
     * @param string $ext The extension to use for the filename.
     * @return string The filename.
     */
    private function createNextFilename($table_name, $ext)
    {
        return ($table_name ?? 'all') . '-' . $this->file_idx . $ext;
    }


    /**
     * Gets the column names for the primary key
     * @tag maybe-error-not-mysql
     *
     * @param string $table The table name.
     * @return array The column names.
     */
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
     *
     * @param resource $handle The file handle to write to.
     * @param string $str The data to write.
     * @param string $next_filename The filename to use for the next file.
     * @return resource The file handle.
     * @throws RuntimeException If the file handle cannot be opened for writing.
     */
    private function sizeCheckWrite($handle, $str, $next_filename)
    {
        $this->file_size += strlen($str);

        if ($this->split_size != 0 and $this->file_size > $this->split_size) {
            fclose($handle);

            $this->file_size = strlen($str);
            $this->file_idx++;

            $handle = fopen($this->filename_prefix . $next_filename, 'w');

            if ($handle === false) {
                throw new RuntimeException('Failed to open file for writing');
            }

            fwrite($handle, $this->hdr);
            $this->generated_files[] = $next_filename;
        }

        fwrite($handle, $str);

        return $handle;
    }


    /**
     * Loads all of the generated export files into a ZIP file
     *
     * @param string $name The name of the archive to create.
     * @return void
     */
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


