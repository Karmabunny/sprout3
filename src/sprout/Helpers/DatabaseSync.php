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

// kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
// :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:

namespace Sprout\Helpers;

use karmabunny\pdb\Compat\DatabaseSync as RealDatabaseSync;
use karmabunny\pdb\Pdb as RealPdb;

/**
* Provides a system for syncing a database to a database definition.
*
* The database definition is stored in one or more XML files, which get merged
* together before the sync is done.
* Contains code that may be MySQL specific.
**/
class DatabaseSync extends RealDatabaseSync
{

    /** @inheritdoc */
    public static function getPdb(): RealPdb
    {
        return Pdb::getInstance();
    }


    /**
     * Load the db_struct.xml from core and from all modules
     */
    public function loadStandardXmlFiles()
    {
        $this->loadXml(APPPATH . 'db_struct.xml');

        $module_paths = Register::getModuleDirs();
        foreach ($module_paths as $path) {
            $path .= '/db_struct.xml';
            if (file_exists($path)) {
                $this->loadXml($path);
            }
        }
    }
}
