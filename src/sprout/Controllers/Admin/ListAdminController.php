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

use karmabunny\pdb\Exceptions\QueryException;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Router;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;


/**
* Any controller which is essentially a short list of items, which are not substantial enough
* to warrant a categories system
*
* Required fields for a list controller table:
*   id
*   name
*   record_order
**/
abstract class ListAdminController extends ManagedAdminController {
    protected $main_order = 'item.record_order';

    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();
    }


    /**
    * Returns the contents of the navigation pane for the list
    **/
    public function _getNavigation()
    {
        $q = "SELECT
                `item`.`id`,
                `item`.`name`
            FROM ~{$this->table_name} AS `item`
            ORDER BY {$this->main_order}";

        $items = Pdb::query($q, [], 'arr');

        $view = new PhpView('sprout/admin/list_navigation');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->items = $items;
        $view->allow_add = $this->main_add;
        $view->record_id = (int) Admin::getRecordId();

        if (Router::$method == 'contents' or Router::$method == 'export') {
            $view->export_refine = '?' . $_SERVER['QUERY_STRING'];
        } else {
            $view->export_refine = '';
        }

        return $view->render();
    }


    /**
    * Returns the tools to show in the left navigation
    **/
    public function _getTools()
    {
        $items = parent::_getTools();

        $items[] = "<li class=\"reorder\"><a href=\"admin/call/{$this->controller_name}/reorder\" onclick=\"$.facebox({'ajax':this.href}); return false;\">Reorder</a></li>";

        return $items;
    }


    /**
     * Deletes an item and logs the deleted data
     *
     * @param int $item_id The record to delete.
     * @return bool True on success, false on failure
     */
    public function _deleteSave($item_id)
    {
        $item_id = (int) $item_id;

        if (!$this->_isDeleteSaved($item_id)) return false;

        $this->deleteRecord($this->table_name, $item_id);

        return true;
    }


    /**
    * Shows the reorder screen (which is shown in a popup box) for re-ordering the children items
    **/
    public function reorder()
    {

        // Get children
        $q = "SELECT id, name
            FROM ~{$this->table_name}
            ORDER BY record_order";
        $children = Pdb::q($q, [], 'arr');

        // If this item only has one child, complain that it's impossible to re-order
        if (count($children) == 1) {
            echo "<p>This item does not have enough items for ordering.</p>";
            return;
        }

        // View
        $view = new PhpView('sprout/admin/categories_reorder');
        $view->items = $children;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        echo $view->render();
    }


    /**
    * Saves a reorder
    **/
    public function reorderSave()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $record_order = 1;
        foreach ($_POST['items'] as $id) {
            $id = (int) $id;

            $q = "UPDATE ~{$this->table_name}
                SET record_order = ?
                WHERE id = ?";
            Pdb::q($q, [$record_order, $id], 'count');

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
        $q = "SELECT record_order
            FROM ~{$this->table_name}
            WHERE id = ?";
        try {
            $order = Pdb::q($q, [$item_id], 'val');
        } catch (QueryException $ex) {
            return;
        }
        if ($order != 0) return;

        $q = "SELECT MAX(record_order) AS m
            FROM ~{$this->table_name}";
        try {
            $order = Pdb::q($q, [], 'val');
        } catch (QueryException $ex) {
            return;
        }

        ++$order;
        $q = "UPDATE ~{$this->table_name}
            SET record_order = ?
            WHERE id = ?";
        Pdb::q($q, [$order, $item_id], 'count');
    }
}
