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
class TreenodeRegexMatcher implements TreenodeMatcher
{
    private $key;
    private $regex;
    private $flags;


    /**
    * Constructor
    *
    * @param string $key The key to search
    * @param mixed $regex The regex to search with. Should not contain leading or trailing slashes.
    * @param string $flags The regex flags to use. Defaults to 'i' (case-insensitive).
    **/
    public function __construct($key, $regex, $flags = 'i')
    {
        $this->key = $key;
        $this->regex = $regex;
        $this->flags = $flags;
    }


    /**
    * Does the match
    *
    * @param TreeNode $node The treenode to do matching against
    * @return True if the node matches, false otherwise
    **/
    public function match($node)
    {
        $search = '/' . str_replace('/', '\/', $this->regex) . '/' . $this->flags;

        if (preg_match($search, $node[$this->key])) return true;
        return false;
    }

    /**
    * Returns true if the children of the specified node should be searched, false otherwise.
    *
    * @param TreeNode $node The treenode which is about to be descended into
    * @return True descending should proceed, false otherwise
    **/
    public function descend($node)
    {
        return true;
    }

    /**
    * Called after children have been processed
    *
    * @param TreeNode $node The treenode which has just ascended.
    **/
    public function ascend ($node) {}
}


