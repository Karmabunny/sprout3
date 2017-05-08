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
* Matches a node which has a specified path.
* Path can be specified with either slash (/) or backslash (\)
**/
class TreenodePathMatcher implements TreenodeMatcher
{
    private $check_path;
    private $curr_depth;

    /**
    * Constructor
    *
    * @param string $path The path to search
    **/
    public function __construct($path)
    {
        $path = strtolower($path);

        $this->check_path = preg_split('![/\\\\]!', $path);
        $this->curr_depth = 0;
    }


    /**
    * Returns true if the children of the specified node should be searched, false otherwise.
    *
    * @param TreeNode $node The treenode which is about to be descended into
    * @return True descending should proceed, false otherwise
    **/
    public function descend($node)
    {
        if ($node->isRoot()) {
            $this->curr_depth = 0;
            return true;
        }

        if ($this->check_path[$this->curr_depth] == strtolower($node->getUrlName())) {
            $this->curr_depth++;
            return true;
        }

        return false;
    }

    /**
    * Does the match
    *
    * @param TreeNode $node The treenode to do matching against
    * @return True if the node matches, false otherwise
    **/
    public function match($node)
    {
        if ($node->isRoot()) {
            $this->curr_depth = 0;
            return false;
        }

        if ($this->curr_depth + 1 != count($this->check_path)) return false;

        if ($this->check_path[$this->curr_depth] == strtolower($node->getUrlName())) {
            return true;
        }

        return false;
    }

    /**
    * Called after children have been processed
    *
    * @param TreeNode $node The treenode which has just ascended.
    **/
    public function ascend ($node) {
        $this->curr_depth--;
    }
}


