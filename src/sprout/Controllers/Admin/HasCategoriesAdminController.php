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

use InvalidArgumentException;

use Sprout\Exceptions\FileMissingException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Category;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\JsonForm;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\Router;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;


/**
* An abstract class for controllers of things which have categories.
**/
abstract class HasCategoriesAdminController extends ManagedAdminController {
    protected string $controller_name;
    protected string $friendly_name;
    protected array $add_defaults = [];
    protected array $main_columns;


    /**
    * Enables re-ordering for categories.
    * You will need a "record_order" column on the category table.
    **/
    protected bool $category_reorder = false;

    /**
    * Enables single-cat mode.
    * This uses radiobuttons instead of checkboxes in the category selection UI.
    **/
    protected bool $category_single = false;

    /**
    * Do we have the 'archive' feature for categories?
    * You will need a "show_admin TINYINT UNSIGNED DEFAULT 1" column on the category table.
    **/
    protected bool $category_archive = false;


    /**
    * Constructor
    **/
    public function __construct()
    {
        if (! $this->main_columns) {
            $this->main_columns = array('Name' => 'name');
        }

        // Add refine fields
        $this->initTableName();
        $records = Pdb::lookup("{$this->table_name}_cat_list");
        $records[0] = 'Uncategorised';

        $this->initRefineBar();
        $this->refine_bar->addWidget(new RefineWidgetSelect('_category_id', 'Category', $records));

        parent::__construct();
    }


    /**
    * Returns TRUE if category archive is enabled, FALSE otherwise
    **/
    public final function getCategoryArchive() {
        return $this->category_archive;
    }


    /**
    * Return true if categories are allowed to be added.
    **/
    public function catAllowAdd()
    {
        return true;
    }


    /**
    * Return true if categories are allowed to be edited.
    **/
    public function catAllowEdit($category_id)
    {
        return true;
    }


    /**
    * Return true if categories are allowed to be deleted.
    **/
    public function catAllowDelete($category_id)
    {
        return true;
    }


    /**
    * Returns the contents of the navigation pane
    **/
    public function _getNavigation()
    {
        $joiner_ref_col = Category::columnMain2joiner($this->table_name);

        $where = '1';
        $columns = [];
        $columns[] = 'categories.id';
        $columns[] = 'categories.name';
        $columns[] = "COUNT(joiner.{$joiner_ref_col}) AS num_items";

        if ($this->category_archive) {
            // GET param or SESSION param
            $columns[] = 'categories.show_admin';

            $_GET['category_type'] = (int) @$_GET['category_type'];
            if (! $_GET['category_type']) {
                $_GET['category_type'] = @$_SESSION['admin']['category_type'];
            }

            // Default
            if (! $_GET['category_type']) {
                $_GET['category_type'] = Constants::CATEGORIES_CURRENT;
            }

            // Where clause and set session
            $clause = Constants::$category_admin_where[$_GET['category_type']];
            if ($clause) {
                $where = $clause;
                $_SESSION['admin']['category_type'] = $_GET['category_type'];
            }
        }

        // Get the category names, and the number of items in each
        $columns = implode(', ', $columns);
        $q = "SELECT {$columns}
            FROM ~{$this->table_name}_cat_list AS categories
            LEFT JOIN ~{$this->table_name}_cat_join AS joiner
                ON categories.id = joiner.cat_id
            WHERE {$where}
            GROUP BY categories.id
            ORDER BY " . ($this->category_reorder ? 'categories.record_order, categories.name' : 'categories.name');
        $res = Pdb::q($q, [], 'arr');

        // Get the number of items which don't have any categories
        $where = implode(' AND ', $this->main_where);
        if (!$where) $where = '1';
        $q = "SELECT COUNT(item.id) AS c
            FROM ~{$this->table_name} AS item
            LEFT JOIN ~{$this->table_name}_cat_join AS joiner ON joiner.{$joiner_ref_col} = item.id
            WHERE joiner.{$joiner_ref_col} IS NULL
              AND {$where}";
        $uncat = (int) Pdb::q($q, [], 'val');

        // If there were any, create an 'uncategorised' meta-category
        if ($uncat) {
            $res[] = array(
                'id' => '0',
                'name' => 'Uncategorised',
                'num_items' => $uncat
            );
        }

        // Create the view and populate it with data
        $view = new PhpView('sprout/admin/categories_navigation');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->category_reorder = $this->category_reorder;
        $view->category_archive = $this->category_archive;
        $view->categories = $res;
        $view->main_add = $this->main_add;

        if ($this->category_archive) {
            $view->category_archive_type = $_GET['category_type'];
        }

        if (Router::$method == 'contents' or Router::$method == 'export') {
            $view->export_refine = '?' . $_SERVER['QUERY_STRING'];
        }

        return $view->render();
    }


    /**
    * Additional categry tools
    **/
    public function _getTools()
    {
        $tools = parent::_getTools();

        if ($this->category_reorder) {
            $tools['reorder'] = "<li class=\"reorder\"><a href=\"admin/extra/{$this->controller_name}_category/reorder_categories\">Reorder Categories</a></li>";
        }

        return $tools;
    }


    /**
     * Generate list of tools for selected admin records
     *
     * @return array [[
     *      url => (string) button url
     *      class => (string) button class
     *      label => (string) button label
     * ]]
     */
    public function _getSelectedTools()
    {
        $list = parent::_getSelectedTools();

        array_unshift($list, [
            'url' => sprintf('SITE/admin/extra/%s/multi_categorise', $this->controller_name),
            'class' => 'selection-action button button-green icon-before icon-edit',
            'label' => 'Categorise',
        ]);

        return $list;
    }


    /**
     * Return the WHERE clause to use for a given key which is provided by the RefineBar
     *
     * Allows custom non-table clauses to be added.
     * Is only called for key names which begin with an underscore.
     * The base table is aliased to 'item'.
     *
     * @param string $key The key name, including underscore
     * @param string $val The value which is being refined.
     * @param array &$query_params Parameters to add to the query which will use the WHERE clause
     * @return string WHERE clause, e.g. "item.name LIKE CONCAT('%', ?, '%')", "item.status IN (?, ?, ?)"
     */
    protected function _getRefineClause($key, $val, array &$query_params)
    {
        $joiner_ref_col = Category::columnMain2joiner($this->table_name);

        switch ($key) {
            case '_category_id':
                if ($val == 0) {
                    // Uncategoried
                    return "(SELECT 1 FROM ~{$this->table_name}_cat_join AS joiner
                        WHERE joiner.{$joiner_ref_col} = item.id LIMIT 1) IS NULL";
                }

                $query_params[] = $val;
                return "(SELECT 1 FROM ~{$this->table_name}_cat_join AS joiner
                    WHERE joiner.{$joiner_ref_col} = item.id AND joiner.cat_id = ? LIMIT 1) = 1";
        }

        return parent::_getRefineClause($key, $val, $query_params);
    }


    /**
    * Returns the main list of records for this controller
    **/
    public function _getContents()
    {
        if (! isset($_GET['page'])) $_GET['page'] = '1';
        $_GET['page'] = (int) $_GET['page'];

        // Apply filter
        list($where, $params) = $this->applyRefineFilter();

        // All records, no category filter
        if (!isset($_GET['_category_id'])) {
            $title = "All <strong>{$this->friendly_name}</strong>";
            $category = null;

        // Uncategorised, no category filter
        } else if ($_GET['_category_id'] === '0') {
            $title = "Uncategorised <strong>{$this->friendly_name}</strong>";
            $category = null;

        // All regular categories, apply category filter
        } else if ($_GET['_category_id'] > 0) {
            $_GET['_category_id'] = (int) $_GET['_category_id'];
            $q = "SELECT * FROM ~{$this->table_name}_cat_list WHERE id = ?";
            $category = Pdb::q($q, [$_GET['_category_id']], 'row');
            $title = "{$this->friendly_name} category <strong>" . Enc::html($category['name']) . "</strong>";

        // Custom filter (refine bar)
        } else {
            $title = "Searching <strong>{$this->friendly_name}</strong>";
            $category = null;
        }

        // Build the where clause
        $has_refine = (bool) count($where);
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        // Determine record order
        $_GET['order'] = preg_replace('/[^_a-z0-9]/', '', $_GET['order'] ?? '');
        $_GET['dir'] ??= '';

        if (!empty($_GET['order'])) {
            Pdb::validateIdentifier($_GET['order']);
            $order = "item.{$_GET['order']}";

            if ($_GET['dir'] == 'asc' or $_GET['dir'] == 'desc') {
                $order .= ' ' . $_GET['dir'];
            } else {
                $_GET['dir'] = 'asc';
            }

        } else if (!empty($_GET['_category_id'])) {
            $joiner_table = Category::tableMain2joiner($this->table_name);
            $joiner_ref_col = Category::columnMain2joiner($this->table_name);

            $cat_id = (int) $_GET['_category_id'];
            $order = "(SELECT record_order
                FROM ~{$joiner_table}
                WHERE {$joiner_ref_col} = item.id AND cat_id = ?
                LIMIT 1)";
            $params[] = $cat_id;

        } else {
            $order = $this->main_order;
            preg_match('/(item\.)?([_a-z]+)( asc| desc)?/i', $this->main_order, $matches);
            $_GET['order'] = trim($matches[2]);
            $_GET['dir'] = trim(!empty($matches[3]) ? strtolower($matches[3]) : 'asc');
        }

        // Get the actual records
        $offset = $this->records_per_page * ($_GET['page'] - 1);
        $q = $this->_getContentsQuery($where, $order, $params);
        $q .= " LIMIT {$this->records_per_page} OFFSET {$offset}";
        $items = Pdb::q($q, $params, 'arr');

        // Get the total number of records
        $total_row_count = Pdb::q("SELECT FOUND_ROWS()", [], 'val');


        // If no mode set, use the session
        // If a mode is set and valid, save in the session
        if (empty($_GET['main_mode'])) {
            $_GET['main_mode'] = @$_SESSION['admin'][$this->controller_name]['main_mode'];
        } else if ($this->main_modes[$_GET['main_mode']]) {
            $_SESSION['admin'][$this->controller_name]['main_mode'] = $_GET['main_mode'];
        }

        // If no valid mode set, use a default
        if (empty($this->main_modes[$_GET['main_mode']])) {
            $_GET['main_mode'] = key($this->main_modes);
        }

        // Build the refine bar
        if ($this->refine_bar) {
            $refine = $this->refine_bar->get();
        } else {
            $refine = '';
        }

        // Build the mode selector ui
        if (count($this->main_modes) > 1) {
            $mode_sel = $this->_modeSelector($_GET['main_mode']);
        } else {
            $mode_sel = '';
        }

        // If there is no records, tell the user
        if ($total_row_count == 0) {
            if ($has_refine) {
                $items_view = '<p>No records were found which match the specified refinements.</p>';
            } else {
                $items_view = '<p>No records currently exist in the database.</p>';
            }
        } else {
            $items_view = $this->_getContentsView($items, $_GET['main_mode'], $category);
        }

        // Build the pagination bar
        $paginate = $this->_paginationBar($_GET['page'], $total_row_count);

        return array(
            'title' => $title,
            'content' => $refine . $mode_sel . $paginate . $items_view . $paginate,
        );
    }


    /**
    * Return HTML for a resultset of items
    * The returned HTML will be sandwiched between the refinebar and the pagination bar.
    *
    * @param Traversable $items The items to render.
    * @param string $mode The mode of the display.
    * @param StdClass $category Category details if a category has been selected.
    **/
    public function _getContentsView($items, $mode, $category)
    {
        return $this->_getContentsViewList($items, $category);
    }


    /**
    * Formats a resultset of items into an Itemlist
    *
    * @param Traversable $items The items to render.
    * @param StdClass $category Category details if a category has been selected.
    **/
    public function _getContentsViewList($items, $category)
    {
        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = $this->main_columns;
        $itemlist->items = $items;
        $itemlist->setCheckboxes(true);
        $itemlist->setOrdering(true);
        $itemlist->setActionsClasses('button button-small');

        // Add the actions
        $itemlist->addAction('edit', "SITE/admin/edit/{$this->controller_name}/%%");
        foreach ($this->main_actions as $name => $url) {
            $itemlist->addAction($name, $url, 'button-grey');
        }
        if ($this->getDuplicateEnabled()) {
            $itemlist->addAction('Duplicate', "SITE/admin/duplicate/{$this->controller_name}/%%", 'button-grey icon-before icon-add');
        }
        if ($this->main_delete) {
            $itemlist->addAction('Delete', "SITE/admin/delete/{$this->controller_name}/%%", 'button button-red icon-before icon-delete');
        }

        // Add classes based on visibility fields
        $visibility = $this->_getVisibilityFields();
        $itemlist->setRowClassesFunc(function($row) use($visibility) {
            $out = '';
            foreach ($visibility as $name => $label) {
                $out .= "main-list--{$name}-{$row[$name]} ";
            }
            return rtrim($out);
        });

        // Prepare view which renders the main content area
        $outer = new PhpView("sprout/admin/generic_itemlist_outer");
        $outer->selected_tools = $this->_getSelectedTools();

        // Build the outer view
        $outer->controller_name = $this->controller_name;
        $outer->friendly_name = $this->friendly_name;
        $outer->category_reorder = $this->category_reorder;
        $outer->itemlist = $itemlist->render();
        $outer->allow_add = $this->main_add;
        $outer->allow_del = $this->main_delete;
        $outer->category = $category;

        return $outer->render();
    }


    /**
    * Called when the import form is being built.
    *
    * Returns HTML of extra options to display, or null if no extra options.
    **/
    protected function _importExtraOptions()
    {
        $view = new PhpView('sprout/admin/categories_import_options');

        // Get the categories
        $cats_table = Category::tableMain2cat($this->table_name);
        $q = "SELECT category.id, category.name
            FROM ~{$cats_table} AS category
            ORDER BY category.name";
        $view->cats = Pdb::q($q, [], 'map');

        Admin::setCategoryTablename($cats_table);
        Admin::setCategorySinglecat($this->category_single);

        return $view;
    }


    /**
    * Called after a record has been inserted or updated.
    *
    * @param int $record_id The id of the record that was inserted or updated.
    * @param array $new_data The new data of the record.
    * @param array $existing_record The old data of the record, which has now been replaced.
    * @param string $type One of 'insert' or 'update'.
    * @param array $raw_data Raw CSV data, with original field names.

    * @return boolean False if any errors are encountered; will cancel the entire import process.
    **/
    protected function _importPostRecord($record_id, $new_data, $existing_record, $type, $raw_data)
    {
        if (! parent::_importPostRecord ($record_id, $new_data, $existing_record, $type, $raw_data)) return false;

        if (@count($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat_id) {
                Category::insertInto($this->table_name, $record_id, $cat_id);
            }
        }

        return true;
    }

    /**
     * Returns a page title and HTML for a form to add a record
     * @return array Two elements: 'title' and 'content'
     */
    public function _getAddForm()
    {
        if (is_array($this->add_defaults)) {
            $data = $this->add_defaults;
        } else {
            $data = [];
        }

        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        Admin::setCategoryTablename("{$this->table_name}_cat_list");
        Admin::setCategorySinglecat($this->category_single);

        // Get the categories
        $q = "SELECT category.id, category.name
            FROM ~{$this->table_name}_cat_list AS category
            ORDER BY category.name";
        $cats = Pdb::q($q, [], 'map');

        if (!isset($data['categories'])) $data['categories'] = [];
        if (!empty($_GET['category_id'])) {
            $data['categories'][] = (int) $_GET['category_id'];
        }

        // Auto-generate form from JSON where possible
        $conf = false;
        try {
            $conf = $this->loadEditJson();
            $view = new PhpView('sprout/auto_edit');
            $view->id = 0;
            $view->config = $conf;

        } catch (FileMissingException $ex) {
            $view_dir = $this->getModulePath();
            $view = new PhpView("{$view_dir}/admin/{$this->controller_name}_add");
        }

        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->data = $data;
        $view->errors = $errors;
        $view->cats = $cats;

        $this->_addPreRender($view);

        // Inflector only works with single words, so only apply to last word
        $words = explode(' ', $this->friendly_name);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);

        return array(
            'title' => 'Adding ' . Enc::html(implode(' ', $words)),
            'content' => $view->render()
        );
    }


    /**
    * Returns the edit form for adding a record
    *
    * @param int $id The id of the record to get the edit form of
    **/
    public function _getEditForm($id)
    {
        $id = (int) $id;

        $joiner_ref_col = Category::columnMain2joiner($this->table_name);

        // Get the item
        $q = "SELECT * FROM ~{$this->table_name} WHERE id = ?";
        try {
            $item = Pdb::q($q, [$id], 'row');
        } catch (RowMissingException $ex) {
            $single = Inflector::singular($this->friendly_name);
            return new AdminError("Invalid id specified - {$single} does not exist");
        }

        $data = $item;

        Admin::setCategoryTablename("{$this->table_name}_cat_list");
        Admin::setCategorySinglecat($this->category_single);

        // Get the categories
        $q = "SELECT category.id, category.name
            FROM ~{$this->table_name}_cat_list AS category
            ORDER BY category.name";
        $cats = Pdb::q($q, [], 'map');

        // Get the selected categories
        if (!isset($data['categories'])) {
            $q = "SELECT cat_id
                FROM ~{$this->table_name}_cat_join
                WHERE {$joiner_ref_col} = ?";
            $data['categories'] = Pdb::q($q, [$id], 'col');
        }

        // Auto-generate form from JSON where possible
        $conf = false;
        try {
            $conf = $this->loadEditJson();
            $view = new PhpView('sprout/auto_edit');
            $view->config = $conf;

            $default_link = Inflector::singular($this->table_name) . '_id';
            $data = array_merge($data, JsonForm::loadMultiEditData($conf, $default_link, $id, []));
            $data = array_merge($data, JsonForm::loadAutofillListData($conf, $this->table_name, $id, []));
        } catch (FileMissingException $ex) {
            $view_dir = $this->getModulePath();
            $view = new PhpView("{$view_dir}/admin/{$this->controller_name}_edit");
        }

        // Overlay session data
        if (!empty($_SESSION['admin']['field_values'])) {
            $data = array_merge($data, $_SESSION['admin']['field_values']);
            unset ($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;
        $view->item = $item;
        $view->data = $data;
        $view->errors = $errors;
        $view->cats = $cats;

        $this->_editPreRender($view, $id);

        // Inflector only works with single words, so only apply to last word
        $words = explode(' ', $this->friendly_name);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);

        $title = 'Editing ' . Enc::html(implode(' ', $words));
        return array(
            'title' => $title . ' <strong>' . Enc::html($this->_identifier($item)) . '</strong>',
            'content' => $view->render()
        );
    }

    /**
    * Returns the edit form for duplicating a record
    *
    * @param int $id The id of the record to get the data from
    **/
    public function _getDuplicateForm($id)
    {
        $id = (int) $id;
        if ($id <= 0) throw new InvalidArgumentException('$id must be greater than 0');

        // Get the item
        $q = "SELECT * FROM ~{$this->table_name} WHERE id = ?";
        $data = $item = Pdb::q($q, [$id], 'row');

        Admin::setCategoryTablename("{$this->table_name}_cat_list");
        Admin::setCategorySinglecat($this->category_single);

        // Get the categories
        $cat_table = Category::tableMain2cat($this->table_name);
        $q = "SELECT category.id, category.name
            FROM ~{$cat_table} AS category
            ORDER BY category.name";
        $cats = Pdb::q($q, [], 'map');

        // Clobber duplication fields with any defaults defined in controller
        if (@count($this->duplicate_defaults)) {
            foreach ($this->duplicate_defaults as $key => $val) {
                $data[$key] = $val;
            }
        }

        // Overlay session data
        if (!empty($_SESSION['admin']['field_values'])) {
            $data = array_merge($data, $_SESSION['admin']['field_values']);
            unset ($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        // Get the selected categories
        if (empty($data['categories'])) {
            $data['categories'] = array();
            $q = "SELECT cat_id
                FROM ~{$this->table_name}_cat_join
                WHERE {$this->controller_name}_id = ?";
            $data['categories'] = Pdb::q($q, [$id], 'col');
        }

        // Auto-generate form from JSON where possible
        $conf = false;
        try {
            $conf = $this->loadEditJson();
            $view = new PhpView('sprout/auto_edit');
            $view->config = $conf;

            $default_link = Inflector::singular($this->table_name) . '_id';
            $data = array_merge($data, JsonForm::loadMultiEditData($conf, $default_link, $id, []));
            $data = array_merge($data, JsonForm::loadAutofillListData($conf, $this->table_name, $id, []));
        } catch (FileMissingException $ex) {
            $view_dir = $this->getModulePath();
            $view = new PhpView("{$view_dir}/admin/{$this->controller_name}_edit");
        }
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;
        $view->item = $item;
        $view->data = $data;
        $view->errors = $errors;
        $view->cats = $cats;

        $this->_duplicatePreRender($view, $id);

        $title = 'Duplicating ' . Enc::html(Inflector::singular($this->friendly_name));
        return array(
            'title' => $title . ' <strong>' . Enc::html($this->_identifier($item)) . '</strong>',
            'content' => $view->render()
        );
    }


    /**
     * Deletes an item and logs the deleted data
     *
     * This method should not be overridden unless absolutely necessary.
     *
     * @param int $item_id The record to delete
     * @return bool True on success, false on failure
     * @throws QueryException
     */
    public function _deleteSave($item_id)
    {
        $item_id = (int) $item_id;

        if (!$this->_isDeleteSaved($item_id)) return false;

        // Start transaction
        $extant_transaction = Pdb::inTransaction();
        if (!$extant_transaction) Pdb::transact();

        // Delete record
        $this->deleteRecord($this->table_name, $item_id);

        // Delete categories
        // N.B. these will already have been deleted if the foreign keys are correctly defined
        $cat_table = Category::tableMain2joiner($this->table_name);
        $record_col = Category::columnMain2joiner($this->table_name);
        Pdb::delete($cat_table, [$record_col => $item_id]);

        // Commit
        if (!$extant_transaction) Pdb::commit();

        return true;
    }


    /**
    * Updates the category table for this controller (so for articles, the updated table will be articles_cat_join)
    * so that the records for the specified item match the category ids provided.
    *
    * @param array $categories A list of category-ids which the specified item should be associated with
    * @param int $id The id of the item to set the categories for
    * @return boolean True on success, false on failure
    *
    * @api
    * @module-api
    **/
    protected function updateCategories($item_id, $categories)
    {
        $item_id = (int) $item_id;
        if (! is_array($categories)) $categories = array();

        $table_name = $this->table_name . '_cat_join';
        $item_column = $this->controller_name . '_id';

        // Find out what is in the db
        $q = "SELECT cat_id FROM ~{$table_name} WHERE {$item_column} = ?";
        $res = Pdb::q($q, [$item_id], 'arr');

        // If it's in the list, remove it from the list, otherwise mark for removal from the db
        $delete = array();
        foreach ($res as $row) {
            $idx = array_search($row['cat_id'], $categories);
            if ($idx === false) {
                $delete[] = $row['cat_id'];
            } else {
                unset ($categories[$idx]);
            }
        }

        // Add everything that is in the list into the db
        if (@count($categories)) {
            foreach ($categories as $cat_id) {
                $cat_id = (int) $cat_id;

                $update_fields = array();
                $update_fields[$item_column] = $item_id;
                $update_fields['cat_id'] = $cat_id;

                Pdb::insert($table_name, $update_fields);

                $this->logAddCategory(Inflector::plural($this->controller_name), $item_id, $cat_id);
            }
        }

        // Remove everything from the delete list
        foreach ($delete as $cat_id) {
            $cat_id = (int) $cat_id;
            $q = "DELETE FROM ~{$table_name} WHERE {$item_column} = ? AND cat_id = ?";
            Pdb::q($q, [$item_id, $cat_id], 'count');

            $this->logDeleteCategory(Inflector::plural($this->controller_name), $item_id, $cat_id);
        }

        return true;
    }


    /**
    * Form to change the categories for a number of records
    **/
    public function _extraMultiCategorise()
    {

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'edit')) {
            return new AdminError('Access denied');
        }

        if (empty($_GET['ids'])) {
            Notification::error('No items selected for categorisation');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        Admin::setCategoryTablename("{$this->table_name}_cat_list");
        Admin::setCategorySinglecat($this->category_single);

        $view = new PhpView('sprout/admin/categories_multi_categorise');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->ids = $_GET['ids'];

        // Get the categories
        $cat_table = Category::tableMain2cat($this->table_name);
        $q = "SELECT category.id, category.name
            FROM ~{$cat_table} AS category
            ORDER BY category.name";
        $view->cats = Pdb::q($q, [], 'map');

        // Get the items
        $params = [];
        $where = Pdb::buildClause([['item.id', 'IN', $_GET['ids']]], $params);
        $q = $this->_getContentsQuery($where, 'item.id', $params);
        $items = Pdb::q($q, $params, 'arr');

        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = $this->main_columns;
        $itemlist->items = $items;
        $view->itemlist = $itemlist->render();


        return $view;
    }

    /**
    * Change the categories for a number of records
    **/
    public function postMultiCategorise()
    {
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'edit')) {
            Notification::error('Access denied');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        if (empty($_POST['ids'])) {
            Notification::error('No items selected for categorisation');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        if (!@is_array($_POST['categories'])) {
            Notification::error('No categories specified for addition');
            Url::redirect('admin/extra/' . $this->controller_name . '/multi_categorise' . '?' . http_build_query($_POST));
        }

        Pdb::transact();

        if (!empty($_POST['mode']) and $_POST['mode'] == 'mod') {
            // Modify categories mode
            foreach ($_POST['ids'] as $item_id) {
                $res = $this->updateCategories($item_id, $_POST['categories']);
                if (! $res) {
                    Notification::error('Database error');
                    Url::redirect('admin/contents/' . $this->controller_name);
                }
            }

        } else {
            // Add categories mode by default
            foreach ($_POST['ids'] as $item_id) {
                foreach ($_POST['categories'] as $cat_id) {
                    Category::insertInto($this->table_name, $item_id, $cat_id);
                }
            }

        }

        Pdb::commit();

        $this->_invalidateCaches('multi_categorise');

        Notification::confirm('Categorisation was successful');
        Url::redirect('admin/contents/' . $this->controller_name);
    }

}
