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
* Interface for matching nodes in the treenode
**/
interface TreenodeMatcher {

    /**
    * Returns true if this node matches the matcher, false if the node does not match.
    *
    * @param TreeNode $node The treenode to do matching against
    * @return True if the node matches, false otherwise
    **/
    public function match ($node);

    /**
    * Returns true if the children of the specified node should be searched, false otherwise.
    *
    * @param TreeNode $node The treenode which is about to be descended into
    * @return True descending should proceed, false otherwise
    **/
    public function descend ($node);

    /**
    * Called after children have been processed. No return value.
    *
    * @param TreeNode $node The treenode which has just ascended.
    **/
    public function ascend ($node);
}


