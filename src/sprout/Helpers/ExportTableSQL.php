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

class ExportTableSQL extends ExportTable
{
    const DATA_NONE = 0;
    const DATA_BOTH = 1;        // insert..update
    const DATA_INSERT = 2;
    const DATA_UPDATE = 3;
    const DATA_CSV = 4;

    public $name;
    public $drop;            // drop existing tables first
    public $structure;        // export the table structure
    public $data;            // how to export the data
    public $where;            // where clause for the table
}


