<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers\Drivers\Archive;

use Exception;

use Sprout\Helpers\Fp;
use Sprout\Helpers\Drivers\ArchiveDriver;


/**
 * Archive library zip driver.
 */
class Zip implements ArchiveDriver
{
    /**
     * @var resource A resource handle to the destination zip file
     */
    protected $file;

    // Compiled directory structure
    protected $dirs = [];

    // Offset location
    protected $offset = 0;

    public function create($paths, $filename = FALSE)
    {
        if (empty($filename)) throw new Exception('filename is required now, sorry');

        // Ensure filename has zip extension
        if (substr($filename, -4) != '.zip') {
            $filename .= '.zip';
        }

        // Sort the paths to make sure that directories come before files
        sort($paths);

        // Create the file in binary write mode
        $this->file = fopen($filename, 'wb');
        if (!$this->file) throw new Exception('Failed to create zip file');

        Fp::log($filename, 'Zip file created');

        // Lock the file
        flock($this->file, LOCK_EX);

        foreach ($paths as $set)
        {
            // Add each path individually
            $this->addData($set[0], $set[1], isset($set[2]) ? $set[2] : NULL);
        }

        // Directory data
        $dirs = implode('', $this->dirs);

        $zipfile =
            $dirs.                              // Directory data
            "\x50\x4b\x05\x06\x00\x00\x00\x00". // Directory EOF
            pack('v', count($this->dirs)).      // Total number of entries "on disk"
            pack('v', count($this->dirs)).      // Total number of entries in file
            pack('V', strlen($dirs)).           // Size of directories
            pack('V', $this->offset).           // Offset to directories
            "\x00\x00";                         // Zip comment length

        if (fwrite($this->file, $zipfile) === false) {
            throw new Exception('Failed to write directory info');
        }

        // Unlock the file
        flock($this->file, LOCK_UN);

        // Close the file
        fclose($this->file);

        Fp::log(filesize($filename), 'Bytes written');
        return true;
    }

    private function dateUnix2dos($timestamp)
    {
        $timestamp = getdate($timestamp);

        if ($timestamp['year'] < 1980)
        {
            return (1 << 21 | 1 << 16);
        }

        $timestamp['year'] -= 1980;

        // What voodoo is this? I have no idea... Geert can explain it though,
        // and that's good enough for me.
        return ($timestamp['year']    << 25 | $timestamp['mon']     << 21 |
                $timestamp['mday']    << 16 | $timestamp['hours']   << 11 |
                $timestamp['minutes'] << 5  | $timestamp['seconds'] >> 1);
    }

    public function addData($file, $name, $contents = NULL)
    {
        // Determine the file type: 16 = dir, 32 = file
        $type = (substr($file, -1) === '/') ? 16 : 32;

        // Fetch the timestamp, using the current time if manually setting the contents
        $timestamp = $this->dateUnix2dos(($contents === NULL) ? filemtime($file) : time());

        // Read the file or use the defined contents
        $data = ($contents === NULL) ? file_get_contents($file) : $contents;

        // Gzip the data, use substr to fix a CRC bug
        $zdata = substr(gzcompress($data), 2, -4);

        $file_data = "\x50\x4b\x03\x04".       // Zip header
                    "\x14\x00".                // Version required for extraction
                    "\x00\x00".                // General bit flag
                    "\x08\x00".                // Compression method
                    pack('V', $timestamp).     // Last mod time and date
                    pack('V', crc32($data)).   // CRC32
                    pack('V', strlen($zdata)). // Compressed filesize
                    pack('V', strlen($data)).  // Uncompressed filesize
                    pack('v', strlen($name)).  // Length of file name
                    pack('v', 0).              // Extra field length
                    $name.                     // File name
                    $zdata;                    // Compressed data

        // Write the zip file
        if (fwrite($this->file, $file_data) === false) {
            throw new Exception('Failed to write file ' . $name);
        }

        $this->dirs[] =
            "\x50\x4b\x01\x02".       // Zip header
            "\x00\x00".               // Version made by
            "\x14\x00".               // Version required for extraction
            "\x00\x00".               // General bit flag
            "\x08\x00".               // Compression method
            pack('V', $timestamp).    // Last mod time and date
            pack('V', crc32($data)).  // CRC32
            pack('V', strlen($zdata)).// Compressed filesize
            pack('V', strlen($data)). // Uncompressed filesize
            pack('v', strlen($name)). // Length of file name
            pack('v', 0).             // Extra field length
            // End "local file header"
            // Start "data descriptor"
            pack('v', 0).             // CRC32
            pack('v', 0).             // Compressed filesize
            pack('v', 0).             // Uncompressed filesize
            pack('V', $type).         // File attribute type
            pack('V', $this->offset). // Directory offset
            $name;                    // File name

        // Set the new offset
        $this->offset += strlen($file_data);
    }

} // End Archive_Zip_Driver Class
