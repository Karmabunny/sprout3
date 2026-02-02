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

use Sprout\Helpers\Archive;
use Sprout\Helpers\Drivers\ArchiveDriver;


/**
 * Archive library bzip driver.
 */
class Bzip implements ArchiveDriver
{

    /**
     * @param array $paths
     * @param string|false $filename
     * @return bool|string Returns string when $filename is false, bool otherwise
     */
    public function create($paths, $filename = FALSE)
    {
        $archive = new Archive('tar');

        foreach ($paths as $set)
        {
            $archive->add($set[0], $set[1]);
        }

        $gzfile = bzcompress($archive->create());

        if ($filename == FALSE)
        {
            return $gzfile;
        }

        if (substr($filename, -8) !== '.tar.bz2')
        {
            // Append tar extension
            $filename .= '.tar.bz2';
        }

        // Create the file in binary write mode
        $file = fopen($filename, 'wb');

        // Lock the file
        flock($file, LOCK_EX);

        // Write the tar file
        $return = fwrite($file, $gzfile);

        // Unlock the file
        flock($file, LOCK_UN);

        // Close the file
        fclose($file);

        return (bool) $return;
    }

    public function addData($file, $name, $contents = NULL)
    {
        return FALSE;
    }

} // End Archive_Bzip_Driver Class
