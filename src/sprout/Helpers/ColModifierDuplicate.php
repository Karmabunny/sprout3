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

/**
* Count duplicates in a column.
**/
class ColModifierDuplicate extends ColModifier
{
    /** @var array */
    private $items;

    /** @var int[] [key => count] */
    protected $_cache = [];

    /**
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @inheritdoc */
    public function modify($val, $field_name)
    {
        $key = $field_name . sha1($val);

        if (isset($this->_cache[$key])) {
            return $this->_cache[$key];
        }

        $count = 0;

        foreach ($this->items as $item) {
            if ($item[$field_name] == $val) {
                $count++;
            }
        }

        $this->_cache[$key] = $count;
        return $count;
    }
}
