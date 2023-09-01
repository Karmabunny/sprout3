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
* Matches a node which has a specific key matching a specific value
**/
class TreenodePageAttrMatcher implements TreenodeMatcher
{
    private $page_ids;


    /**
    * Constructor
    *
    * @param string $attr_name The attr name to search.
    * @param mixed $attr_value The attr value to search for.
    *    Can be null to match all pages which contain the attribute, irrespective of the value.
    **/
    public function __construct($attr_name, $attr_value = null)
    {
        $this->page_ids = Page::pagesWithAttr($attr_name, $attr_value);
    }


    /**
    * Does the match
    *
    * @param Treenode $node The treenode to do matching against
    * @return bool True if the node matches, false otherwise
    **/
    public function match($node)
    {
        if (in_array($node['id'], $this->page_ids)) return true;
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


