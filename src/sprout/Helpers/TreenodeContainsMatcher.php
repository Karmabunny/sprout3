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
* @package Tree
**/

/**
* Matches a node which has a specific key containing a specific value
**/
class TreenodeContainsMatcher implements TreenodeMatcher
{
    private $key;
    private $value;

    /**
    * Constructor
    *
    * @param string $key The key to search
    * @param mixed $value The value to search for
    **/
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }


    /**
    * Does the match
    *
    * @param Treenode $node The treenode to do matching against
    * @return bool True if the node matches, false otherwise
    **/
    public function match($node)
    {
        if (stripos($node[$this->key], $this->value) !== false) return true;
        return false;
    }

    /**
    * Returns true if the children of the specified node should be searched, false otherwise.
    *
    * @param Treenode $node The treenode which is about to be descended into
    * @return bool True descending should proceed, false otherwise
    **/
    public function descend($node)
    {
        return true;
    }

    /**
    * Called after children have been processed
    *
    * @param Treenode $node The treenode which has just ascended.
    **/
    public function ascend ($node) {}
}


