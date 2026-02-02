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

use Sprout\Helpers\Drivers\ArchiveDriver;


/**
 * Archive library tar driver.
 */
class Tar implements ArchiveDriver
{

    // Compiled archive data
    protected $data = '';

    /**
     * @param array $paths
     * @param string|false $filename
     * @return bool|string Returns string when $filename is false, bool otherwise
     */
    public function create($paths, $filename = FALSE)
    {
        // Sort the paths to make sure that directories come before files
        sort($paths);

        foreach ($paths as $set)
        {
            // Add each path individually
            $this->addData($set[0], $set[1], isset($set[2]) ? $set[2] : NULL);
        }

        $tarfile = implode('', $this->data).pack('a1024', ''); // EOF

        if ($filename == FALSE)
        {
            return $tarfile;
        }

        if (substr($filename, -3) != 'tar')
        {
            // Append tar extension
            $filename .= '.tar';
        }

        // Create the file in binary write mode
        $file = fopen($filename, 'wb');

        // Lock the file
        flock($file, LOCK_EX);

        // Write the tar file
        $return = fwrite($file, $tarfile);

        // Unlock the file
        flock($file, LOCK_UN);

        // Close the file
        fclose($file);

        return (bool) $return;
    }

    public function addData($file, $name, $contents = NULL)
    {
        // Determine the file type
        $type = is_dir($file) ? 5 : (is_link($file) ? 2 : 0);

        // Get file stat
        $stat = stat($file);

        // Get path info
        $path = pathinfo($file);

        // File header
        $tmpdata =
            pack('a100', $name). // Name of file
            pack('a8',   sprintf('%07o',  $stat[2])). // File mode
            pack('a8',   sprintf('%07o',  $stat[4])). // Owner user ID
            pack('a8',   sprintf('%07o',  $stat[5])). // Owner group ID
            pack('a12',  sprintf('%011o', $type === 2 ? 0 : $stat[7])). // Length of file in bytes
            pack('a12',  sprintf('%011o', $stat[9])). // Modify time of file
            pack('a8',   str_repeat(chr(32), 8)). // Reserved for checksum for header
            pack('a1',   $type). // Type of file
            pack('a100', $type === 2 ? readlink($file) : ''). // Name of linked file
            pack('a6',   'ustar'). // USTAR indicator
            pack('a2',    chr(32)). // USTAR version
            pack('a32',  'Unknown'). // Owner user name
            pack('a32',  'Unknown'). // Owner group name
            pack('a8',   chr(0)). // Device major number
            pack('a8',   chr(0)). // Device minor number
            pack('a155', $path['dirname'] === '.' ? '' : $path['dirname']). // Prefix for file name
            pack('a12',  chr(0)); // End

        $checksum = pack('a8',
                        sprintf('%07o',
                            array_sum(
                                array_map('ord', str_split(substr($tmpdata, 0, 512))))));

        $this->data[] = substr_replace($tmpdata, $checksum, 148, 8) .
                        str_pad(file_get_contents($file), (ceil($stat[7] / 512) * 512), chr(0));
    }

} // End Archive_Tar_Driver Class
