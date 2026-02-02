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
namespace Sprout\Helpers;

use Kohana_Exception;

use Sprout\Helpers\Drivers\ArchiveDriver;


/**
 * Class for creating archives in various formats - zip, tar.bz2, etc
 */
class Archive
{

    // Files and directories
    protected $paths = [];

    // Driver instance
    protected $driver;

    /**
     * Loads the archive driver.
     *
     * @throws  Kohana_Exception
     * @param   string|null $type Type of archive to create
     * @return  void
     */
    public function __construct($type = NULL)
    {
        $type = empty($type) ? 'zip' : $type;

        // Set driver name
        $driver = 'Sprout\\Helpers\\Drivers\\Archive\\' . ucfirst($type);

        // Load the driver
        if (!class_exists($driver)) throw new \Exception('Unknown archive type: ' . $type);

        // Initialize the driver
        $this->driver = new $driver();

        // Validate the driver
        if ( ! ($this->driver instanceof ArchiveDriver))
            throw new Kohana_Exception('core.driver_implements', $type, get_class($this), 'ArchiveDriver');
    }

    /**
     * Adds files or directories, recursively, to an archive.
     *
     * @param   string $path File or directory to add
     * @param   string|null $name Name to use for the given file or directory
     * @param   bool|null $recursive Add files recursively, used with directories
     * @return  object
     */
    public function add($path, $name = NULL, $recursive = NULL)
    {
        // Normalize to forward slashes
        $path = str_replace('\\', '/', $path);

        // Set the name
        empty($name) and $name = $path;

        if (is_dir($path))
        {
            // Force directories to end with a slash
            $path = rtrim($path, '/').'/';
            $name = rtrim($name, '/').'/';

            // Add the directory to the paths
            $this->paths[] = array($path, $name);

            if ($recursive === TRUE)
            {
                $dir = opendir($path);
                while (($file = readdir($dir)) !== FALSE)
                {
                    // Do not add hidden files or directories
                    if ($file[0] === '.')
                        continue;

                    // Add directory contents
                    $this->add($path.$file, $name.$file, TRUE);
                }
                closedir($dir);
            }
        }
        else
        {
            $this->paths[] = array($path, $name);
        }

        return $this;
    }

    /**
     * Creates an archive and saves it into a file.
     *
     * @throws  Kohana_Exception
     * @param   string $filename Archive filename
     * @return  boolean
     */
    public function save($filename)
    {
        // Get the directory name
        $directory = pathinfo($filename, PATHINFO_DIRNAME);

        if ( ! is_writable($directory))
            throw new Kohana_Exception('archive.directory_unwritable', $directory);

        if (is_file($filename))
        {
            // Unable to write to the file
            if ( ! is_writable($filename))
                throw new Kohana_Exception('archive.filename_conflict', $filename);

            // Remove the file
            unlink($filename);
        }

        return $this->driver->create($this->paths, $filename);
    }

    /**
     * Creates a raw archive file and returns it.
     *
     * @return  string
     */
    public function create()
    {
        return $this->driver->create($this->paths);
    }

} // End Archive
