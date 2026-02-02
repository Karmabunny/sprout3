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

use Kohana;

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Category;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;

/**
* This is a generic controller which category controllers should extend.
**/
abstract class CategoryAdminController extends ManagedAdminController
{
    protected $controller_name;
    protected $friendly_name;
    protected $main_columns = ['Name' => 'name'];
    protected $add_defaults = array();


    /**
    * The view to use for adding new category records. Loaded in a popup
    **/
    protected $add_view_name = 'sprout/admin/categories_add';

    /**
    * The view to use for editing existing category records
    **/
    protected $edit_view_name = 'sprout/admin/categories_edit';


    /**
     * Instance of the parent controller
     *
     * @var HasCategoriesAdminController
     */
    protected $parent_inst;


    public function __construct()
    {
        $controller_name = $this->getControllerName();

        $base_name = str_replace('_category', '', $controller_name);
        $this->table_name = Category::tableMain2cat(Inflector::plural($base_name));

        $parent_class = preg_replace('/CategoryAdminController$/', 'AdminController', get_class($this));

        $this->parent_inst = Sprout::instance($parent_class, HasCategoriesAdminController::class);

        if (!$this->friendly_name) {
            $this->friendly_name = $this->parent_inst->getFriendlyName() . ' Categories';
        }

        if (!$this->navigation_name) {
            $this->navigation_name = $this->parent_inst->getNavigationName();
        }

        parent::__construct();
    }


    /** @inheritdoc */
    public static function _getContentPermissionGroups(): array
    {
        return [];
    }


    /**
    * Return the instance of the parent controller
    **/
    public final function getParentInst(): HasCategoriesAdminController {
        return $this->parent_inst;
    }


    /**
    * Proxy for top nav name => the main controller class
    **/
    public function getTopnavName()
    {
        return $this->parent_inst->getTopnavName();
    }

    /**
    * Proxy for navigation => the main controller class
    **/
    public function _getNavigation()
    {
        return $this->parent_inst->_getNavigation();
    }

    /**
    * Proxy for tools => the main controller class
    **/
    public function _getTools()
    {
        return $this->parent_inst->_getTools();
    }


    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     *
     * Key is the field name, value is the field label
     *
     * @return array
     */
    public function _getVisibilityFields()
    {
        return [];
    }


    /**
    * Returns the add form for adding a record
    *
    * @return array The HTML code which represents the add form
    **/
    public function _getAddForm()
    {
        if (! $this->parent_inst->catAllowAdd()) {
            return [
                'title' => 'Cannot add',
                'content' => '<p><i>'
                    . 'Contact ' . Enc::html(Kohana::config('branding.support_organisation')) . ' to have categories added'
                    . '</i></p>'
            ];
        }

        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset($_SESSION['admin']['field_values']);
        } else {
            $data = $this->add_defaults;
        }

        $view = new PhpView($this->add_view_name);
        $view->controller_name = $this->controller_name;
        $view->data = $data;
        if (!empty($_SESSION['admin']['field_errors'])) {
            $view->errors = $_SESSION['admin']['field_errors'];
        } else {
            $view->errors = [];
        }

        $this->_addPreRender($view);

        return array(
            'title' => 'Adding ' . Enc::html(Inflector::singular($this->friendly_name)),
            'content' => $view->render()
        );
    }


    /**
    * Saves the provided POST data into a new record in the database
    *
    * @param int $item_id After saving, the new record id will be returned in this parameter
    * @return bool True on success, false on failure
    **/
    public function _addSave(&$item_id)
    {
        $_SESSION['admin']['field_values'] = $_POST;

        $valid = new Validator($_POST);
        $valid->required(['name']);
        $valid->check('name', 'Validity::length', 0, 50);
        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        Pdb::transact();

        $update_fields = [];
        $update_fields['name'] = $_POST['name'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $item_id = Pdb::insert($this->table_name, $update_fields);

        Pdb::commit();

        return true;
    }


    /**
    * Return HTML which represents the form for editing a record
    *
    * @param int $id The id of the record to get the edit form of
    **/
    public function _getEditForm($id)
    {
        if (! $this->parent_inst->catAllowEdit($id)) {
            return [
                'title' => 'Cannot edit',
                'content' => '<p><i>Contact ' . Enc::html(Kohana::config('branding.support_organisation'))
                . ' to have this category edited</i></p>'
            ];
        }

        $id = (int) $id;

        // Get the item
        $q = "SELECT * FROM ~{$this->table_name} WHERE id = ?";
        $item = Pdb::query($q, [$id], 'row');
        $data = $item;

        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset ($_SESSION['admin']['field_values']);
        }

        // Build and execute the view
        $view = new PhpView($this->edit_view_name);
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;
        $view->item = $item;
        $view->data = $data;
        if (!empty($_SESSION['admin']['field_errors'])) {
            $view->errors = $_SESSION['admin']['field_errors'];
        } else {
            $view->errors = [];
        }
        $view->category_archive = $this->parent_inst->getCategoryArchive();

        $this->_editPreRender($view, $id);

        return array(
            'title' => 'Editing ' . Enc::html(Inflector::singular($this->friendly_name)) . ' <strong>' . Enc::html($this->_identifier($item)) . '</strong>',
            'content' => $view->render()
        );
    }


    /**
    * Saves the provided POST data the specified record
    *
    * @param int $item_id The record to update
    * @return bool True on success, false on failure
    **/
    public function _editSave($item_id)
    {
        if (! $this->parent_inst->catAllowEdit($item_id)) {
            Notification::error('Unable to edit category');
            Url::redirect('admin/edit/' . $this->controller_name . '/' . $item_id);
        }

        $item_id = (int) $item_id;

        // Validate
        $valid = new Validator($_POST);
        $valid->required(['name']);
        $valid->check('name', 'Validity::length', 0, 50);
        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_values'] = $_POST;
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        // Start transaction
        Pdb::transact();

        // Update item
        $update_fields = [];
        $update_fields['name'] = $_POST['name'];

        if ($this->parent_inst->getCategoryArchive()) {
            $update_fields['show_admin'] = $_POST['show_admin'];
        }

        Pdb::update($this->table_name, $update_fields, ['id' => $item_id]);

        // Commit
        Pdb::commit();

        return true;
    }


    /**
    * Shows delete form for deleting this category
    *
    * @param int $id The category id
    **/
    public function _getDeleteForm($id)
    {
        if (! $this->parent_inst->catAllowDelete($id)) {
            return '<p><i>Contact ' . Enc::html(Kohana::config('branding.support_organisation'))
                . ' to have this category deleted.</i></p>';
        }

        $main_controller = str_replace('_category', '', $this->controller_name);
        $main_table = Inflector::plural($main_controller);
        $cat_table = Category::tableMain2cat($main_table);
        $joiner_table = Category::tableMain2joiner($main_table);

        $q = "SELECT * FROM ~{$cat_table} WHERE id = ?";
        $category = Pdb::q($q, [$id], 'row');

        $q = "SELECT COUNT(main.id) AS C
            FROM ~{$main_table} AS main
            INNER JOIN ~{$joiner_table} AS joiner ON joiner.{$main_controller}_id = main.id
            WHERE joiner.cat_id = ?";
        $num_in_cat = Pdb::q($q, [$id], 'val');

        $view = new PhpView('sprout/admin/categories_delete');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->main_columns = $this->main_columns;

        $view->id = $id;
        $view->category = $category;
        $view->num_in_cat = $num_in_cat;

        return array(
            'title' => 'Delete category <strong>' . Enc::html($category['name']) . '</strong>',
            'content' => $view->render()
        );
    }

    /**
    * Deletes a category
    *
    * @param int $id The record to delete
    * @return bool True on success, false on failure
    **/
    public function _deleteSave($id)
    {
        $id = (int) $id;

        if (! $this->parent_inst->catAllowDelete($id)) {
            Notification::error('Unable to delete category');
            Url::redirect('admin/delete/' . $this->controller_name . '/' . $id);
        }

        $main_controller = str_replace('_category', '', $this->controller_name);
        $main_table = Inflector::plural($main_controller);
        $cat_table = Category::tableMain2cat($main_table);
        $joiner_table = Category::tableMain2joiner($main_table);


        Pdb::transact();

        // If required, delete the children record (might be quite slow...)
        if ($_POST['mode'] == 'cont') {
            $q = "SELECT main.id
                FROM ~{$main_table} AS main
                INNER JOIN ~{$joiner_table} AS joiner ON joiner.{$main_controller}_id = main.id
                WHERE joiner.cat_id = ?
                ORDER BY main.id";
            $res = Pdb::q($q, [$id], 'col');

            foreach ($res as $row_id) {
                $res = $this->parent_inst->_deleteSave($row_id);
                if (! $res) return false;
            }
        }

        // Delete category
        $this->deleteRecord($this->table_name, $id);

        // Delete references to category
        // N.B. these will already have been deleted if the foreign keys are correctly defined
        Pdb::delete($joiner_table, ['cat_id' => $id]);

        Pdb::commit();

        return true;
    }


    /**
    * Shows the reorder page for re-ordering the items for this category
    **/
    public function _extraReorder($category_id)
    {
        $category_id = (int) $category_id;

        if (! AdminPerms::controllerAccess($this->parent_inst->getControllerName(), 'reorder')) {
            return new AdminError('Access denied');
        }

        $item_name = str_replace('_category', '', $this->controller_name);
        $item_table = Inflector::plural($item_name);
        $joiner_table = Category::tableMain2joiner($item_table);

        // Load the category
        $q = "SELECT * FROM ~{$this->table_name} WHERE id = ?";
        try {
            $page = Pdb::q($q, [$category_id], 'row');
        } catch (RowMissingException $ex) {
            return new AdminError('Invalid id specified - category does not exist');
        }

        // Items in the category
        $q = "SELECT item.*
            FROM ~{$item_table} AS item
            INNER JOIN ~{$joiner_table} AS joiner
                ON item.id = joiner.{$item_name}_id
            WHERE joiner.cat_id = ?
            ORDER BY joiner.record_order";
        $items = Pdb::q($q, [$category_id], 'arr');

        if (count($items) < 2) {
            return new AdminError('This category does not have enough items in it for re-ordering.');
        }

        foreach ($items as &$item) {
            $item['name'] = $this->parent_inst->_identifier($item);
        }

        // View
        $view = new PhpView('sprout/admin/categories_reorder');
        $view->id = $category_id;
        $view->page = $page;
        $view->items = $items;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        return $view->render();
    }


    /**
    * Saves a item reorder
    **/
    public function reorderSave($category_id)
    {
        $category_id = (int) $category_id;
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->parent_inst->getControllerName(), 'reorder')) {
            Notification::error('Access denied');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        $item_name = str_replace('_category', '', $this->controller_name);
        $joiner_table = Category::tableMain2joiner(Inflector::plural($item_name));

        $record_order = 1;
        foreach ($_POST['items'] as $id) {
            $id = (int) $id;

            $where = ['cat_id' => $category_id, "{$item_name}_id" => $id];
            Pdb::update($joiner_table, ['record_order' => $record_order], $where);

            $record_order++;
        }

        Notification::confirm('Re-order was successful');
        Url::redirect("admin/contents/{$item_name}?_category_id={$category_id}");
    }



    /**
    * Shows the reorder page for re-ordering the categories.
    **/
    public function _extraReorderCategories()
    {

        if (! AdminPerms::controllerAccess($this->parent_inst->getControllerName(), 'categories')) {
            return new AdminError('Access denied');
        }

        // Load the category
        $q = "SELECT id, name FROM ~{$this->table_name} ORDER BY record_order, name";
        $items = Pdb::q($q, [], 'arr');

        if (count($items) < 2) {
            return new AdminError('There are not enough categories for re-ordering.');
        }

        // View
        $view = new PhpView('sprout/admin/categories_reorder');
        $view->action = 'admin/call/' . $this->controller_name . '/reorderCategoriesSave';
        $view->items = $items;

        return $view->render();
    }


    /**
    * Saves a item reorder
    **/
    public function reorderCategoriesSave()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->parent_inst->getControllerName(), 'categories')) {
            Notification::error('Access denied');
            Url::redirect('admin/contents/' . $this->controller_name);
        }


        $record_order = 1;
        foreach ($_POST['items'] as $id) {
            $id = (int) $id;

            Pdb::update($this->table_name, ['record_order' => $record_order], ['id' => $id]);

            $record_order++;
        }

        $item_name = str_replace('_category', '', $this->controller_name);
        Notification::confirm('Category re-order was successful');
        Url::redirect("admin/contents/{$item_name}");
    }


    /**
     * Archive a category
     */
    public function ajaxArchiveAction($category_id)
    {
        if (!Csrf::check()) die('Bad token');

        $data = [];
        $data['show_admin'] = 0;
        Pdb::update($this->table_name, $data, ['id' => $category_id]);

        echo 'OK';
    }


    /**
     * Unarchive a category
     */
    public function ajaxUnarchiveAction($category_id)
    {
        if (!Csrf::check()) die('Bad token');

        $data = [];
        $data['show_admin'] = 1;
        Pdb::update($this->table_name, $data, ['id' => $category_id]);

        echo 'OK';
    }

}
