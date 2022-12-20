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

use ArrayAccess;
use Exception;


/**
* @package Tree
**/

/**
* Represents a node in the tree
**/
class Treenode implements ArrayAccess
{
    protected $data = array();
    private $real_children = array();
    private $filtered_children = null;

    public $parent = null;


    /**
    * Creates a node, with the specified data
    *
    * @param array $data Initial data for the node
    **/
    public function __construct($data = null)
    {
        if (! $data) return;
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }


    /**
    * Returns true if this node is the root node
    **/
    public function isRoot()
    {
        if ($this->parent) return false;
        return true;
    }



    /* TREE LOADING ----------------------------------------------*/

    /**
    * Loads a flat table into a tree structure.
    * Requires the table to have an id, parent_id and record_order field.
    *
    * @param string $table The name of the table to load
    * @param array $conditions Query conditions, in the format used by {@see Pdb::buildClause}
    * @param string $order The column to order by. Defaults to 'record_order'
    * @return Treenode The loaded tree
    **/
    public static function loadTree($table, array $conditions = ['1'], $order = 'record_order')
    {
        Pdb::validateIdentifier($table);
        Pdb::validateIdentifier($order);

        $binds = array();
        $where = Pdb::buildClause($conditions, $binds);

        $q = "SELECT *
            FROM ~{$table}
            WHERE {$where}
            ORDER BY parent_id, {$order}";
        $res = Pdb::query($q, $binds, 'pdo');

        $nodes = array();
        foreach ($res as $row) {
            $nodes[] = new Treenode($row);
        }

        $res->closeCursor();

        $root_node = new Treenode(array('id' => 0));

        // Assign nodes to their parents
        $orphans = array();
        do {
            $num_processed = 0;
            foreach ($nodes as $index => $node) {
                $parent = $root_node->findNodeValue('id', $node['parent_id']);

                if ($parent) {
                    $parent->children[] = $node;
                    $node->parent = $parent;
                    unset ($nodes[$index]);
                    unset ($orphans[$node['id']]);
                    $num_processed++;

                } else {
                    $orphans[$node['id']] = $node;
                }
            }
            if ($num_processed == 0) break;

        } while (count($nodes));

        return $root_node;
    }


    /* TREE SEARCHING --------------------------------------------*/

    /**
    * Finds a node by looking at this node
    * If that does not match, looking at each of this nodes children in turn.
    * If that does not match, return null
    *
    * @param mixed $key The key to search for in the data
    * @param mixed $value The value to search for in the data
    * @return TreeNode|null if found, null if not found.
    **/
    public function findNodeValue($key, $value)
    {
        if ($this->data[$key] == $value) {
            return $this;
        }

        foreach ($this->children as $child) {
            $res = $child->findNodeValue ($key, $value);
            if ($res) return $res;
        }

        return null;
    }


    /**
    * Finds a node by looking at this node
    * If that does not match, looking at each of this nodes children in turn.
    * If that does not match, return null
    *
    * @param TreenodeMatcher $matcher The matcher to use for finding the node.
    * @return static|null if found, null if not found.
    **/
    public function findNode(TreenodeMatcher $matcher)
    {
        if ($matcher->match($this)) {
            return $this;
        }

        $res = $matcher->descend($this);
        if (! $res) return null;

        foreach ($this->children as $child) {
            $res = $child->findNode ($matcher);
            if ($res) return $res;
        }

        $matcher->ascend($this);

        return null;
    }


    /**
    * Finds all nodes which match the specified matcher.
    *
    * @param TreenodeMatcher $matcher The matcher to use for finding the nodes.
    * @return static[] The found nodes, or an empty array if no nodes were found.
    **/
    public function findAllNodes(TreenodeMatcher $matcher)
    {
        $nodes = array();

        if ($matcher->match($this)) {
            $nodes[] = $this;
        }

        $res = $matcher->descend($this);
        if (! $res) return $nodes;

        foreach ($this->children as $child) {
            $res = $child->findAllNodes ($matcher);
            if ($res) {
                $nodes = array_merge($nodes, $res);
            }
        }

        $matcher->ascend($this);

        return $nodes;
    }


    /**
    * Finds all ancestors of a specific node, including the node, not including the root node of the tree
    * The array is in order from top to bottom, so $ancestors[0] is the top-parent, and $ancestors[len-1] is the node.
    *
    *         A
    *    B         C
    *  D   E     F   G
    * H I
    *
    * NODE    RESULT ARRAY
    *  H       B D H
    *  D       B D
    *  B       B
    *  A       (empty)
    *
    * @return static[] The ancestors, as described above.
    **/
    public function findAncestors()
    {
        $ancestors = array();
        $node = $this;
        while ($node->data['id'] != 0) {
            $ancestors[] = $node;
            $node = $node->parent;
        }

        $ancestors = array_reverse($ancestors);

        return $ancestors;
    }


    /**
    * Filter the children of this node, removing any children which don't match the specified TreenodeMatcher.
    * The nodes are not actually removed, matching nodes are just added to a filtered children list
    * Which is returned instead of the original children list.
    **/
    public function filterChildren(TreenodeMatcher $matcher)
    {
        $this->filtered_children = array();

        $res = $matcher->descend($this);
        if (! $res) return;

        foreach ($this->real_children as $node) {
            if ($matcher->match($node)) {
                $this->filtered_children[] = $node;
                $node->filterChildren($matcher);
            }
        }

        $matcher->ascend($this);
    }


    /**
    * Removes the currently active filter
    **/
    public function removeFilter()
    {
        $this->filtered_children = null;

        foreach ($this->real_children as $node) {
            $node->removeFilter();
        }
    }


    /**
    * Returns an array of all children nodes, including sub-children
    *
    * The array will be id => name.
    * This function requires the table to have a column named 'name'.
    * The name field will be indented according to the depth.
    * If specified, the exclude_id argument will be used to
    * exclude nodes (and their children) according to id.
    **/
    public function getAllChildren($exclude_id = null, $indent_str = '     ', $indent = 0)
    {
        $output = array();
        foreach ($this->children as $node) {
            if ($exclude_id == $node->data['id']) continue;

            $output[$node->data['id']] = str_repeat($indent_str, $indent) . $node->data['name'];

            $children = $node->getAllChildren($exclude_id, $indent_str, $indent + 1);
            foreach ($children as $id => $name) {
                $output[$id] = $name;
            }
        }

        return $output;
    }


    /**
     *
     * @return static[]
     */
    public function & getChildren()
    {
        if ($this->filtered_children !== null) {
            return $this->filtered_children;
        }
        return $this->real_children;
    }


    /**
    * Is this node an orphan?
    * Orphans are at the top of the tree, and they don't have any children.
    *
    * @return bool if it's an orphan, false otherwise
    **/
    public function isOrphan()
    {
        if ($this->parent and !$this->parent->isRoot()) return false;
        if (count($this->children) != 0) return false;

        return true;
    }



    /* NODE DATA -------------------------------------------------*/

    /**
    * Generic field getter for unknown properties - used for TreeNode->children
    *
    * Be aware that data gotten through this getter is BY REFERENCE
    * For by-value data retrival, use the array-access functions.
    *
    * @param string $field The field to get.
    **/
    public function &__get($field) {
        if ($field == 'children') {
            return $this->getChildren();
        }

        throw new Exception("Invalid usage of \$node->__get() for field '{$field}'; use array methods (\$node['{$field}']) instead.");
    }

    /**
    * Generic field getter for unknown properties - used for TreeNode->children
    *
    * @param string $field The field to set.
    * @param mixed $value The value to set.
    **/
    public function __set($field, $value)
    {
        if ($field == 'children') {
            $this->real_children = $value;
            return;
        }

        throw new Exception("Invalid usage of \$node->__set() for field '{$field}'; use array methods (\$node['{$field}']) instead.");
    }

    /**
    * ArrayAccess function for checking if a specified key exists
    * @param mixed $offset The offset to check.
    **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
    * ArrayAccess function for getting a value by its key
    * @param mixed $offset The offset to get.
    **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * ArrayAccess function for setting a value by its key
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess function for unsetting a value
     * @param mixed $offset The offset to remove.
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset ($this->data[$offset]);
    }

}


