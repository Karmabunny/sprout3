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
namespace Sprout\Helpers\Drivers;


/**
 * Archive driver interface.
 */
interface ArchiveDriver {

    /**
     * Creates an archive and optionally, saves it to a file.
     *
     * @param   array    filenames to add
     * @param   string   file to save the archive to
     * @return  boolean
     */
    public function create($paths, $filename = FALSE);

    /**
     * Add data to the archive.
     *
     * @param   string   filename
     * @param   string   name of file in archive
     * @return  void
     */
    public function addData($file, $name, $contents = NULL);

} // End ArchiveDriver Interface
