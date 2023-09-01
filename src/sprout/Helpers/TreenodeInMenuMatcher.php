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
class TreenodeInMenuMatcher implements TreenodeMatcher
{

    /**
    * Does the match
    *
    * @param Treenode $node The treenode to do matching against
    * @return bool True if the node matches, false otherwise
    **/
    public function match($node)
    {
        if (! UserPerms::checkPermissionsTree('pages', $node['id'])) return false;
        if ($node['show_in_nav']) return true;
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


