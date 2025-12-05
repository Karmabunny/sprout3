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

use Exception;


/**
* Facilitates the cimple importing of CSV files
* Loads up the CSV and allows for sequential reading of the file as data records.
**/
class ImportCSV
{
    private $handle;
    private $line;
    private $headings;


    /**
     * Opens a CSV for reading
     *
     * @param string|resource $filename If a string, the file with that name will be opened as a resource
     * @param array|null $headings Headings for the columns in the CSV.
     *        If not provided, they will be extracted from the first row of the CSV
     */
    public function __construct($filename, ?array $headings = null)
    {
        if (is_string($filename)) {
            $this->handle = @fopen($filename, 'r');
        } else if (is_resource($filename)) {
            $this->handle = $filename;
        } else {
            throw new Exception('Invalid argument');
        }

        if ($headings) {
            $this->headings = $headings;
        } else {
            $this->headings = fgetcsv($this->handle);
            foreach ($this->headings as &$val) {
                $val = trim($val);
            }
        }
    }

    /**
    * Get the headings of this CSV
    **/
    public function getHeadings()
    {
        return $this->headings;
    }

    /**
    * Get a line of the CSV
    **/
    public function getLine()
    {
        if ($this->handle == null) return null;

        $line = fgetcsv($this->handle);
        if ($line == false) {
            fclose($this->handle);
            $this->handle = null;
            return null;
        }

        return $line;
    }

    /**
    * Get a line, and transpose the header line, to create an assoc. array of data
    **/
    public function getNamedLine()
    {
        $line = $this->getLine();
        if (! $line) return null;

        $out = array_flip($this->headings);
        foreach ($out as $key => $idx) {
            $out[$key] = $line[$idx];
        }

        return $out;
    }

}
