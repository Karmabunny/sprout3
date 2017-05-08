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
class TreenodeHasWidgetMatcher implements TreenodeMatcher
{
    private $ids = array();


    /**
    * Constructor
    *
    * @param string $type The type of widget to search
    **/
    public function __construct($type)
    {
        $q = "SELECT rev.page_id
            FROM ~page_revisions AS rev
            INNER JOIN ~page_widgets AS widget ON widget.page_revision_id = rev.id AND widget.active = 1
            WHERE widget.type = ?
            AND rev.status = 'live'
            ORDER BY rev.page_id";

        $this->ids = Pdb::query($q, [$type], 'col');
    }


    /**
    * Does the match
    *
    * @param TreeNode $node The treenode to do matching against
    * @return True if the node matches, false otherwise
    **/
    public function match($node)
    {
        if (in_array($node['id'], $this->ids)) return true;
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
    public function ascend($node) {}
}


