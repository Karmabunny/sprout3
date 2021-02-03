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

namespace Sprout\Controllers\Admin;

use Sprout\Exceptions\FileMissingException;
use Sprout\Exceptions\RowMissingException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Treenode;
use Sprout\Helpers\Url;
use Sprout\Helpers\View;


/**
* Any controller which is essentially a tree-based structure of nodes and sub-nodes.
*
* Required fields for a tree controller table:
*   id
*   name
*   parent_id
*   record_order
*
* @tag api
* @tag module-api
**/
abstract class TreeAdminController extends ManagedAdminController {

    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        $this->add_defaults['parent_id'] = @$_GET['parent_id'];
    }


    /**
    * Returns the contents of the navigation pane for the tree
    **/
    public function _getNavigation()
    {
        $nodes_string = '';
        if (@count($_SESSION['admin'][$this->controller_name . '_nav']) > 0) {
            $nodes_string = "'" . implode ("', '", $_SESSION['admin'][$this->controller_name . '_nav']) . "'";
        }

        $view = new View('sprout/admin/tree_navigation');
        $view->nodes_string = $nodes_string;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        return $view->render();
    }


    /**
    * Returns the tools to show in the left navigation
    **/
    public function _getTools()
    {
        $items = parent::_getTools();

        if (AdminAuth::isSuper()) {

            $items[] = "<li class=\"reorder\"><a href=\"admin/call/{$this->controller_name}/reorderTop\" onclick=\"$.facebox({'ajax':this.href}); return false;\">Reorder top-level</a></li>";
        }

        $items[] = "<li class=\"config\"><a href=\"admin/extra/{$this->controller_name}/organise\">Organise tree</a></li>";

        return $items;
    }


    /**
    * Pre-render hook for adding
    **/
    protected function _addPreRender($view)
    {
        parent::_addPreRender($view);

        $root = Treenode::loadTree($this->table_name);
        $view->tree_nodes = $root->getAllChildren();
    }

    /**
    * Pre-render hook for editing
    **/
    protected function _editPreRender($view, $item_id)
    {
        $root = Treenode::loadTree($this->table_name);
        $view->tree_nodes = $root->getAllChildren($item_id);
    }


    /**
     * Return HTML which represents the form for deleting a record
     *
     * @param int $item_id The record to show the delete form for
     * @return array Two HTML elements with keys 'title' and 'content'
     */
    public function _getDeleteForm($item_id)
    {
        $item_id = (int) $item_id;

        try {
            $view = new View("{$this->getModulePath()}/admin/{$this->controller_name}_delete");
        } catch (FileMissingException $ex) {
            $view = new View("sprout/admin/tree_delete");
        }
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $item_id;

        // Load item details
        try {
            $view->item = Pdb::get($this->table_name, $item_id);
        } catch (RowMissingException $ex) {
            return [
                'title' => 'Error',
                'content' => "Invalid id specified - {$this->controller_name} does not exist",
            ];
        }

        // Child items
        $root = Treenode::loadTree($this->table_name);
        $node = $root->findNodeValue('id', $item_id);
        $children = ($node->children ? $node->children : []);
        foreach ($children as $child) {
            if (!$child->children) continue;
            foreach ($child->children as $descendent) {
                $children[] = $descendent;
            }
        }
        $view->children = $children;

        return [
            'title' => 'Deleting ' . Enc::html(Inflector::singular($this->friendly_name)) . ' <strong>' . Enc::html($this->_identifier($view->item)) . '</strong>',
            'content' => $view->render()
        ];
    }


    /**
     * Deletes an item and logs the deleted data
     *
     * @param int $item_id The record to delete.
     * @param int $depth Used for recursion.
     * @param int $log_id Log ID referring to deleted parent, if applicable
     * @return bool True on success, false on failure
     */
    final public function _deleteSave($item_id, $depth = 0, $log_id = 0)
    {
        $item_id = (int) $item_id;

        if (!$this->_isDeleteSaved($item_id)) return false;

        // Start transaction
        if ($depth == 0) {
            $extant_transaction = Pdb::inTransaction();
            if (!$extant_transaction) Pdb::transact();
        }

        // Delete parent
        $parent_log_id = $log_id;
        if ($log_id > 0) {
            $this->deleteRecord($this->table_name, $item_id, $log_id);
        } else if ($depth == 0 and $log_id == 0) {
            $parent_log_id = $this->deleteRecord($this->table_name, $item_id);
        } else {
            $this->deleteRecord($this->table_name, $item_id);
        }

        // Delete children
        $q = "SELECT id FROM ~{$this->table_name} WHERE parent_id = ?";
        $children = Pdb::q($q, [$item_id], 'col');

        foreach ($children as $child_id) {
            $res = $this->_deleteSave($child_id, $depth + 1, $parent_log_id);
            if (! $res) return false;
        }

        // Commit
        if ($depth == 0) {
            if (!$extant_transaction) Pdb::commit();
        }

        return true;
    }


    /**
    * Shows the reorder screen (which is shown in a popup box) for re-ordering the children items
    **/
    public function reorder($id)
    {
        $id = (int) $id;

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'reorder')) {
            echo "<p>Access denied.</p>";
            return;
        }

        if ($id == 0) {
            echo "<p>Re-ordering of this item is not possible.</p>";
            return;
        }

        // Load it
        $q = "SELECT * FROM ~{$this->table_name} WHERE id = ?";
        try {
            $item = Pdb::q($q, [$id], 'row');
        } catch (RowMissingException $ex) {
            echo "<p>Invalid id specified - item does not exist</p>";
            return;
        }

        // Get children
        $q = "SELECT id, name
            FROM ~{$this->table_name}
            WHERE parent_id = ?
            ORDER BY record_order";
        $children = Pdb::q($q, [$id], 'arr');

        // If this item does not have any children, use the parent instead
        if (count($children) == 0) {
            echo $this->reorder($item->parent_id);
            return;
        }

        // If this item only has one child, complain that its impossible to re-order
        if (count($children) == 1) {
            echo "<p>This item does not have enough children for ordering.</p>";
            return;
        }

        // View
        $view = new View('sprout/admin/categories_reorder');
        $view->id = $id;
        $view->items = $children;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        echo $view->render();
    }


    /**
    * Shows the reorder screen (which is shown in a popup box) for re-ordering the top-level stuff
    **/
    public function reorderTop()
    {

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'reorder')) {
            echo "<p>Access denied.</p>";
            return;
        }

        // Get children
        $q = "SELECT id, name
            FROM ~{$this->table_name}
            WHERE parent_id = 0
            ORDER BY record_order";
        $children = Pdb::q($q, [], 'arr');

        // If there is only one child, complain that its impossible to re-order
        if (count($children) == 1) {
            echo "<p>This site does not have enough top-level items for ordering.</p>";
            return;
        }

        // View
        $view = new View('sprout/admin/categories_reorder');
        $view->id = 0;
        $view->items = $children;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        echo $view->render();
    }

    /**
    * Saves a tree reorder
    **/
    public function reorderSave($parent_id)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'reorder')) {
            Notification::error('Access denied');
            Url::redirect('admin/contents/' . $this->getControllerName());
        }

        $parent_id = (int) $parent_id;

        $record_order = 1;

        foreach ($_POST['items'] as $id) {
            $id = (int) $id;

            $where = ['id' => $id, 'parent_id' => $parent_id];
            Pdb::update($this->table_name, ['record_order' => $record_order], $where);

            $record_order++;
        }

        $this->_invalidateCaches('reorder');

        Notification::confirm('Re-order was successful');
        Url::redirect("admin/intro/" . $this->controller_name);
    }


    /**
     * If the specified item needs a record number to be set,
     * Puts this item at the end of the list.
     *
     * @param int $item_id Record-id to update
     */
    protected function fixRecordOrder($item_id)
    {
        $q = "SELECT record_order, parent_id FROM ~{$this->table_name} WHERE id = ?";
        $item = Pdb::q($q, [$item_id], 'row');

        if ($item['record_order'] != 0) return;

        $q = "SELECT MAX(record_order) AS m
            FROM ~{$this->table_name}
            WHERE parent_id = ?";
        $order = 1 + Pdb::query($q, [$item['parent_id']], 'val');

        Pdb::update($this->table_name, ['record_order' => $order], ['id' => $item_id]);
    }


    /**
     * Tree organisation tool
     * Bulk renaming, reordering and reparenting
     */
    public function _extraOrganise() {
        $view = new View('sprout/admin/tree_organise');
        $view->root = Treenode::loadTree($this->table_name, ['1'], 'record_order');
        $view->controller_name = $this->controller_name;

        return array(
            'title' => 'Organise ' . Enc::html($this->friendly_name),
            'content' => $view->render()
        );
    }


    /**
     * Save tree organise form submission, see {@see self::_extraOrganise}
     * @return void Admin will be redirected to a follow-up page
     */
    public function organiseAction()
    {
        Csrf::checkOrDie();

        $nodes = json_decode($_POST['data'], true);
        if (@count($nodes) == 0) {
            Notification::error('Failed to read submitted change data');
            Url::redirect('admin/extra/' . $this->controller_name . '/organise');
        }

        Pdb::transact();

        $deletes = [];
        foreach ($nodes as $node) {
            $node['id'] = (int) @$node['id'];
            if (!$node['id']) continue;

            if (isset($node['deleted']) and $node['deleted'] == 1) {
                // Delete
                $deletes[] = $node['id'];

            } else {
                $node['parent'] = (int) $node['parent'];
                $node['order'] = (int) $node['order'];
                $node['name'] = trim($node['name']);

                // Update existing record
                if (!$node['name']) continue;
                if (!$node['order']) continue;

                $update_data = [];
                $update_data['name'] = $node['name'];
                $update_data['parent_id'] = $node['parent'];
                $update_data['record_order'] = $node['order'];
                $update_data['date_modified'] = Pdb::now();
                Pdb::update($this->table_name, $update_data, ['id' => $node['id']]);
            }
        }

        // Deletes are delayed until all other updates
        // Depth of 1 prevents a transaction
        if (AdminAuth::isSuper()) {
            foreach ($deletes as $id) {
                $this->_deleteSave($id, 1);
            }
        }

        Pdb::commit();

        Notification::confirm('Your changes have been saved');
        Url::redirect('admin/extra/' . $this->controller_name . '/organise');
    }


    /**
    * Returns the children for a specific item, in a format required by jqueryFileTree.
    * Uses the POST param 'dir', and is usually run through an AJAX call.
    **/
    public function filetreeOpen()
    {
        $_POST['dir'] = trim($_POST['dir']);
        $parent_id = (int) basename($_POST['dir']);


        echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

        // This item
        $dir_item_path = preg_replace('!^/(.+)/$!', '$1', $_POST['dir']);
        if ($dir_item_path != '/') {
            $top_node = Pdb::get($this->table_name, $parent_id);

            $name = $top_node['name'];
            if (strlen($name) > 25) $name = substr($name, 0, 25) . '...';
            $name = Enc::html($name);

            $rel = Enc::html('/' . $dir_item_path);

            echo "<li class=\"file ext_txt allow-access directory-item\"><a href=\"#\" rel=\"{$rel}\">{$name}</a></li>";
        }

        // Get children
        $q = "SELECT child.id, child.name, COUNT(sub.id) AS num_children
            FROM ~{$this->table_name} AS child
            LEFT JOIN ~{$this->table_name} AS sub ON sub.parent_id = child.id
            WHERE child.parent_id = ?
            GROUP BY child.id
            ORDER BY child.record_order, child.id";
        $children = Pdb::q($q, [$parent_id], 'arr');

        // Children of this item
        foreach ($children as $child) {
            $name = $child['name'];
            if (strlen($name) > 25) $name = substr($name, 0, 25) . '...';
            $name = Enc::html($name);

            $rel = Enc::html($_POST['dir'] . $child['id']);

            if ($child['num_children'] > 0) {
                echo "<li class=\"directory collapsed allow-access\"><a href=\"#\" rel=\"{$rel}/\">{$name}</a></li>";
            } else {
                echo "<li class=\"file ext_txt allow-access\"><a href=\"#\" rel=\"{$rel}\">{$name}</a></li>";
            }
        }

        echo "</ul>";

        if ($dir_item_path != '/') {
            echo "<p class=\"tree-extras\">";
            echo "&#43; <a href=\"SITE/admin/add/{$this->controller_name}?parent_id={$parent_id}\">Add Child</a>";
            echo " &nbsp; ";
            echo "&#8597; <a href=\"SITE/{$this->controller_name}/reorder/{$parent_id}\" onclick=\"$.facebox({'ajax':this.href}); return false;\">Re-order</a>";
            echo "</p>";
        }

        if (empty($_SESSION['admin'][$this->controller_name . '_nav'])) {
            $_SESSION['admin'][$this->controller_name . '_nav'] = array();
        }
        if ($_POST['dir'] != '/' and !in_array ($_POST['dir'], $_SESSION['admin'][$this->controller_name . '_nav'])) {
            $_SESSION['admin'][$this->controller_name . '_nav'][] = $_POST['dir'];
        }
    }

    /**
    * Saves in the session data the currently open items in navigation tree
    * Uses the POST param 'dir', and is usually run through an AJAX call.
    **/
    public function filetreeClose()
    {
        if (@count($_SESSION['admin'][$this->controller_name . '_nav']) == 0) return;

        $index = array_search ($_POST['dir'], $_SESSION['admin'][$this->controller_name . '_nav']);
        unset ($_SESSION['admin'][$this->controller_name . '_nav'][$index]);
    }
}


