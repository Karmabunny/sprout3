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

    /**
     * The name of the table.
     * @var string
     */
    public $name;

    /**
     * Drop existing tables first.
     * @var bool
     */
    public $drop;

    /**
     * Export the table structure.
     * @var bool
     */
    public $structure;

    /**
     * How to export the data.
     * @var int one of DATA constants
     */
    public $data;

    /**
     * Where clause for the table.
     * @var string
     */
    public $where;
}


