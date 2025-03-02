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

use Exception;
use InvalidArgumentException;

use Sprout\Controllers\Controller;
use karmabunny\pdb\Exceptions\ConstraintQueryException;
use Sprout\Exceptions\FileMissingException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Kohana;
use Sprout\Exceptions\WorkerJobException;
use Sprout\Helpers\AI\AI;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Email;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\ImportCSV;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Json;
use Sprout\Helpers\JsonForm;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\PerRecordPerms;
use Sprout\Helpers\QueryTo;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Session;
use Sprout\Helpers\Tags;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\WorkerCtrl;
use Sprout\Helpers\Register;
use Traversable;

/**
* This is a generic controller which all controllers which are managed in the admin area should extend.
*
* Required fields for a managed controller table:
*   id
*
* @tag api
* @tag module-api
**/
abstract class ManagedAdminController extends Controller {
    /**
     * This is the shorthand name of the controller.
     *
     * DO NOT declare this in the extending class. This is derived from the
     * shortname used to register the controller.
     *
     * @see Register::adminControllers()
     * @see getControllerName()
     * @var string
     */
    protected $controller_name;

    /**
     * This is the friendly name of the controller.
     *
     * In 99% of cases, should be the plural form of the controller name.
     *
     * If this is not set, this is extracted from the class name.
     *
     * @see getFriendlyName()
     * @var string
     */
    protected $friendly_name;

    /**
     * The friendly name used in the sidebar navigation.
     *
     * Defaults to matching the friendly name.
     *
     * @see getNavigationName()
     * @var string
     */
    protected $navigation_name;

    /**
     * This is the name of the table to get data from.
     *
     * Will be automatically inferred from the controller name if not specified.
     *
     * @see getTableName()
     * @var string
     */
    protected $table_name;

    /**
    * Default values used for adding a record.
    **/
    protected $add_defaults;

    /**
    * Default values used for duplicating a record.
    **/
    protected $duplicate_defaults = array(
        'name' => '',
    );

    /**
    * Any tables / multiedits to have data emptied before duplicated.
    * e.g. "operator_cat_join"
    **/
    protected $duplicate_omit_table_joints = array();

    /**
    * The columns to use for the main view
    **/
    protected $main_columns;

    /**
    * Order of main view records
    **/
    protected $main_order = 'item.name';

    /**
    * An additional where clause for the main view
    **/
    protected $main_where = array();

    /**
    * Actions for the itemlist
    **/
    protected $main_actions = array();

    /**
    * Should a link be shown above the list for adding records? (default yes)
    **/
    protected $main_add = true;

    /** Is deletion allowed, with an option shown in the UI? (default: no) */
    protected $main_delete = false;

    /**
    * Different modes available for the main view
    * By default, there is only one mode: list
    **/
    protected $main_modes = array();

    /**
    * The columns to allow import for
    **/
    protected $import_columns;

    /**
    * The default selection for the "duplicates" option
    * Values are "new", "merge", "merge_blank" and "skip".
    **/
    protected $import_duplicates = '';

    /**
    * Typically, we don't want to import the ID, and just let autoinc do it's thing
    **/
    protected $import_id_column = false;

    /**
    * If a client is providing CSVs which don't have headings
    * You'll need to provide them in this array
    **/
    protected $import_headings = null;

    /**
    * Modifiers applied to data prior to export
    * Should be a class which extends ColModifier
    * Can be an object instance or string of a class name
    **/
    protected $export_modifiers = array();

    /** Should this controller log add/edit/delete actions? */
    protected $action_log = true;

    /** Is this controller enabled for automated email report sending? */
    protected $email_reports = true;

    /**
    * Defines the widgets for the refine bar
    **/
    protected $refine_bar;

    /**
    * The default number of records to show per page
    **/
    protected $records_per_page = 50;

    /**
    * Flag to turn duplication on or off
    **/
    protected $duplicate_enabled = false;

    /**
    * Should a UI for editing the "subsite_id" field on a record be shown?
    * If enabled by extending classes, then the table should contain a 'subsite_id' INT UNSIGNED column
    **/
    protected $per_subsite = false;


    /**
    * Constructor. This must be called in the extending class.
    **/
    public function __construct()
    {
        // Backwards compat.
        $this->getControllerName();
        $this->getFriendlyName();
        $this->getNavigationName();
        $this->getTableName();

        if ($this->main_columns) {
            foreach ($this->main_columns as $col) {
                if ($col === 'name') {
                    if (!$this->main_columns) $this->main_columns = array('Name' => 'name');
                    if (!$this->import_columns) $this->import_columns = array('name');
                    break;
                }
            }
        }

        $this->initRefineBar();

        $this->refine_bar->setGroup('General');
        $this->refine_bar->addWidget(new RefineWidgetSelect('_date_modified', 'Date modified', Constants::$recent_dates));
        $this->refine_bar->addWidget(new RefineWidgetSelect('_date_added', 'Date added', Constants::$recent_dates));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('_all_tag', 'All the tags'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('_any_tag', 'Any of the tags'));

        $this->main_modes = array('list' => array('Details', 'list')) + $this->main_modes;

        Session::instance();

        parent::__construct();
    }


    /**
     * Initialises the refine bar if it isn't already set, with a search widget for the 'name' field if it exists
     * Most controllers which need a custom refine bar should call this before adding their own search widgets
     * @return void The new {@see RefineBar} is set as $this->refine_bar
     */
    protected function initRefineBar()
    {
        if ($this->refine_bar) return;

        $this->refine_bar = new RefineBar();
        if (!$this->main_columns) return;
        foreach ($this->main_columns as $col) {
            if ($col === 'name') {
                $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));
                return;
            }
        }
    }


    /**
     * Initialises the table name if it isn't already set, using the plural of the shorthand controller name
     *
     * @deprecated use getTableName()
     * @return void
     */
    protected function initTableName()
    {
        if ($this->table_name) return;
        $this->getTableName();
    }


    /**
     * Get the controller shortname.
     *
     * @return string
     */
    final public function getControllerName(): string
    {
        if ($this->controller_name) {
            return $this->controller_name;
        }

        $this->controller_name = Register::getAdminControllerShorthand(static::class);
        return $this->controller_name;
    }

    /**
     * Get the controller friendly name.
     *
     * @return string
     */
    final public function getFriendlyName(): string
    {
        if ($this->friendly_name) {
            return $this->friendly_name;
        }

        $this->friendly_name = Admin::generateFriendlyName($this->getControllerName());
        return $this->friendly_name;
    }

    /**
     * Get the controller navigation name.
     *
     * @return string
     */
    final public function getNavigationName(): string
    {
        if ($this->navigation_name) {
            return $this->navigation_name;
        }

        $this->navigation_name = $this->getFriendlyName();
        return $this->navigation_name;
    }

    /**
     * Returns the defined table name
     *
     * @return string
     */
    final public function getTableName(): string
    {
        if ($this->table_name) {
            return $this->table_name;
        }

        $this->table_name = Inflector::plural($this->getControllerName());
        return $this->table_name;
    }

    /**
     * Gets the name of the controller to use for the top nav
     *
     * @return string
     */
    public function getTopnavName()
    {
        return $this->getControllerName();
    }

    /**
    * Returns the duplication enabling flag
    **/
    final public function getDuplicateEnabled() {
        return $this->duplicate_enabled;
    }

    /**
    * If true, then a UI for editing the "subsite_id" for a record should be shown
    **/
    final public function isPerSubsite() {
        return $this->per_subsite;
    }


    /**
     * Get the permission groups for this controller.
     *
     * This determines the controller will appear in either:
     *
     * - `record`: 'Per-record permissions'
     * - `operator_category`: 'Per-tab permissions'
     *
     * @return bool[] [ record, operator_category ]
     */
    public static function _getContentPermissionGroups(): array
    {
        $permissions = [];
        $permissions['record'] = true;
        $permissions['operator_category'] = true;
        return $permissions;
    }


    /**
    * Returns the intro HTML for this controller.
    **/
    public function _intro()
    {
        Url::redirect('admin/contents/' . $this->controller_name);
    }


    /**
    * Returns the SQL query for use by the export tools.
    * The query MUST NOT include a LIMIT clause.
    *
    * @param string $where A where clause to use.
    * Generated based on the specified refine options.
    **/
    protected function _getExportQuery($where = '1')
    {
        $q = "SELECT item.*
            FROM ~{$this->table_name} AS item
            WHERE {$where}
            ORDER BY item.id";

        return $q;
    }


    /**
    * Returns the SQL query for use by ai automation tools.
    * This wraps the export query by default, so a change there will apply to both
    * However, it allows you to split them for inclusion or amendment of data for AI tooling
    *
    * The query MUST NOT include a LIMIT clause.
    *
    * @param string $where A where clause to use.
    * Generated based on the specified refine options.
    **/
    protected function _getAiDataQuery($where = '1')
    {
        return $this->_getExportQuery($where);
    }


    /**
     * Applies filters defined in the query string using a LIKE contains
     * Only fields which exist in the RefineBar will be filtered
     * @param array $source_data Source data, e.g. $_GET or $_POST
     * @return array Three elements:
     *         [0] (array) WHERE clauses, to be joined by the calling code with AND
     *         [1] (array) Params to use in a Pdb::q call which uses the generated WHERE clauses
     *         [2] (array) Key-value pairs containing filter options extracted from the $_GET data
     */
    protected function applyRefineFilter(?array $source_data = null)
    {
        if (empty($source_data)) {
            $source_data = $_GET;
        }
        $where = [];
        $params = [];
        $fields = [];
        foreach ($source_data as $key => $val) {
            if (!$this->refine_bar->hasField($key)) continue;

            $val = trim($val);
            if ($val == '') continue;

            $fields[$key] = $val;

            if ($key[0] == '_') {
                $str = $this->_getRefineClause($key, $val, $params);
                if ($str) $where[] = $str;
            } else {
                $op = $this->refine_bar->getOperator($key);

                // If operator is not specified then auto-determine; strings CONTAINS, numbers =
                if (empty($op)) {
                    if (preg_match('/^[-+]?([0-9]+\.)?[0-9]+$/', $val)) {
                        $op = '=';
                    } else {
                        $op = 'CONTAINS';
                    }
                }

                $conditions = [["item.{$key}", $op, $val]];
                $where[] = Pdb::buildClause($conditions, $params);
            }
        }
        return [$where, $params, $fields];
    }


    /**
    * Returns form for doing exports
    **/
    public function _getExport()
    {
        $export = new PhpView("sprout/admin/generic_export");
        $export->controller_name = $this->controller_name;
        $export->friendly_name = $this->friendly_name;

        // Build the refine bar, adding the 'category' field if required
        if ($this->refine_bar) {
            $export->refine = $this->refine_bar->get();
        }

        // Apply filter
        [$where, $params, $export->refine_fields] = $this->applyRefineFilter();
        [$cols, $items] = $this->getRefinedDataPreview($where, $params, 'export');

        // Create the itemlist for the preview section
        if (count($items) == 0) {
            $export->itemlist = '<p><i>No records found which match the refinebar clauses specified.</i></p>';

        } else {
            $itemlist = new Itemlist();
            $itemlist->main_columns = $cols;
            $itemlist->items = $items;
            $export->itemlist = $itemlist->render();
        }

        return array(
            'title' => 'Export for ' . strtolower($this->friendly_name),
            'content' => $export->render(),
        );
    }


    /**
    * Returns form for doing AI bulk content reprocessing
    **/
    public function _getAiReprocess()
    {
        if (!AI::isEnabled()) {
            return array(
                'title' => 'AI not configured',
                'content' => '<p>AI tooling is not currently configured. Please apply appropriate settings.</p>',
            );
        }

        $view = new PhpView("sprout/admin/generic_content_ai");
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        // Build the refine bar, adding the 'category' field if required
        if ($this->refine_bar) {
            $view->refine = $this->refine_bar->get();
        }

        // Apply filter
        [$where, $params, $view->refine_fields] = $this->applyRefineFilter();
        [$cols, $items] = $this->getRefinedDataPreview($where, $params, 'ai');

        // Create the itemlist for the preview section
        if (count($items) == 0) {
            $view->itemlist = '<p><i>No records found which match the refinebar clauses specified.</i></p>';

        } else {
            $itemlist = new Itemlist();
            $itemlist->main_columns = $cols;
            $itemlist->items = $items;
            $view->itemlist = $itemlist->render();
        }

        // Make the names pretty
        $db_columns = array();
        foreach ($cols as $col) {
            $db_columns[$col] = ucfirst(str_replace('_', ' ', $col));
        }
        asort($db_columns);

        $ai_view = new PhpView("sprout/admin/generic_import_ai");
        $ai_view->headings = $db_columns;
        $ai_view->db_columns = $db_columns;
        $view->ai_view = $ai_view->render();

        return array(
            'title' => 'AI bulk content editing for ' . strtolower($this->friendly_name),
            'content' => $view->render(),
        );
    }


    /**
     * Get a preview data set for tools using custom refine bars
     *
     * This includes export and AI reprocessing
     *
     * @param array $where
     * @param array $params
     * @param $type export|ai
     * @return (array)[]
     */
    private function getRefinedDataPreview(array $where, array $params, string $type )
    {
        // Query which gets three records for the preview
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        $q = match ($type) {
            'ai' => $this->_getAiDataQuery($where) . ' LIMIT 3',
            'export' => $this->_getExportQuery($where) . ' LIMIT 3',
            default => throw new InvalidArgumentException('Invalid type'),
        };
        $items = Pdb::q($q, $params, 'arr');

        // Clean up fields which are too large and build the column list
        $cols = array();
        $modifiers = $this->export_modifiers;
        foreach ($items as &$row) {
            if (count($cols) == 0) {
                foreach ($row as $key => $junk) {
                    if (isset($modifiers[$key]) and $modifiers[$key] === false) continue;
                    $cols[$key] = $key;
                }
            }

            foreach ($row as $key => &$val) {
                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key, $row);
                }
            }

            foreach ($row as $key => &$val) {
                if ($val && strlen($val) > 50) $val = substr($val, 0, 50) . '...';
            }
        }

        return [$cols, $items];
    }


    /**
    * Does the actual export. Return false on error.
    *
    * @return false|array [
    *    'type' => the content type
    *    'filename' => filename
    *    'data' => the data itself
    * ]
    **/
    public function _exportData()
    {
        $filename = strtolower(str_replace(' ', '_', $this->friendly_name)) . '_' . date('Y-m-d');

        // Apply filter
        list($where, $params) = $this->applyRefineFilter($_POST);


        // Query which gets the CSV records
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        $q = $this->_getExportQuery($where);
        $res = Pdb::query($q, $params, 'pdo');


        // Do the export
        switch ($_POST['format']) {
            case 'csv':
                $data = QueryTo::csv($res, $this->export_modifiers);
                if (! $data) return false;

                return ['type' => 'text/csv; charset=UTF-8', 'filename' => "{$filename}.csv", 'data' => $data];

            case 'xml':
                $data = QueryTo::xml($res, $this->export_modifiers);
                if (! $data) return false;

                return ['type' => 'application/xml', 'filename' => "{$filename}.xml", 'data' => $data];

            case 'json':
                $data = QueryTo::json($res, $this->export_modifiers);
                if (! $data) return false;

                return ['type' => 'application/json', 'filename' => "{$filename}.json", 'data' => $data];
        }

        // Is closed by QueryTo::csv, but remains open otherwise
        $res->closeCursor();

        return false;
    }


    /**
    * Perform AI reprocessing and enqueue new requests.
    *
    * @return bool
    **/
    public function _aiReprocessData()
    {
        // Apply filter
        list($where, $params) = $this->applyRefineFilter($_POST);

        // Query which gets the CSV records
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        $q = $this->_getExportQuery($where);
        $reprocess_rows = Pdb::query($q, $params, 'arr');

        $ai_config_fields = $_POST['multiedit_ai_fields'] ?? [];
        $activation = $_POST['activation_status'] ?? false;

        // Do post-import processing for AI handlers
        foreach ($reprocess_rows as $reprocess) {
            $res = $this->_importPostRecordAi($reprocess['id'], [], [], 'update', $reprocess, $ai_config_fields, $activation);
            if (! $res) return false;
        }

        // This by default will redirect to a worker job if AI is enabled
        $info = $this->_importPostAi();
        if ($info === false) return false;

        if (is_array($info)) {
            Notification::confirm('AI Background Job created.', 'plain', 'default', [$info['log_url'] => 'View AI progress']);
            return true;
        }

        return false;
    }


    /**
    * Returns a form which contains options for doing an export
    **/
    public function _getImport($filename)
    {
        $csv = new ImportCSV($filename, $this->import_headings);
        $headings = $csv->getHeadings();

        // Build data sample
        $sample = array();
        $num = 0;
        while ($line = $csv->getNamedLine()) {
            foreach ($line as $col => $val) {
                if ($val) $sample[$col][] = $val;
            }
            if ($num++ >= 3) break;
        }

        // Find columns in database table
        $q = "SHOW COLUMNS FROM ~{$this->table_name}";
        $res = Pdb::q($q, [], 'arr-num');

        // Make the names pretty
        $db_columns = array();
        foreach ($res as $row) {
            $db_columns[$row[0]] = ucfirst(str_replace('_', ' ', $row[0]));
        }
        asort($db_columns);

        // Try to auto-match to import fields
        $data = array();
        $data['duplicates'] = 'new';
        foreach ($headings as $idx => $h) {
            $csv_heading = trim($headings[$idx]);

            $found_col = $this->_importColGuess($csv_heading);

            if (!$found_col) {
                foreach ($db_columns as $col => $name) {
                    if (strcasecmp($col, $csv_heading) == 0) { $found_col = $col; break; }
                    if (strcasecmp($name, $csv_heading) == 0) { $found_col = $col; break; }
                }
            }

            if ($found_col) {
                $data['columns'][Enc::httpfield($csv_heading)] = $found_col;
            }
        }

        // Replace the pre-filled values with session values if found
        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset ($_SESSION['admin']['field_values']);
        }

        // Prepare the view
        try {
            $view = new PhpView("sprout/admin/{$this->controller_name}_import");
        } catch (Exception $ex) {
            $view = new PhpView("sprout/admin/generic_import");
        }
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->headings = $headings;
        $view->sample = $sample;
        $view->import_columns = $db_columns;
        $view->data = $data;
        $view->duplicate_options = ($this->import_duplicates == '');
        $view->extra_options = $this->_importExtraOptions();
        $view->ai_options = $this->_importAiOptions($headings, $db_columns, $data);

        $title = 'Import ' . strtolower($this->friendly_name);

        return array(
            'title' => $title,
            'content' => $view->render(),
        );
    }


    /**
    * Does the actual import
    *
    * @param string $filename The location of the import data, in a temporary directory
    **/
    public function _importData($filename)
    {
        $_SESSION['admin']['field_values'] = Validator::trim($_POST);

        $csv = new ImportCSV($filename, $this->import_headings);
        $headings = $csv->getHeadings();

        $real_from_post = array();
        foreach ($headings as $name) {
            $real_from_post[Enc::httpfield($name)] = $name;
        }

        if ($this->import_duplicates) {
            $_POST['duplicates'] = $this->import_duplicates;
        }

        $type = null;
        $record_id = null;
        $match_db = $match_csv = null;

        $error = false;
        $valid = new Validator($_POST);
        $valid->required(['duplicates']);

        if ($_POST['duplicates'] != 'new') {
            $valid->required(['match_field']);
        }

        if (empty($_POST['columns'])) {
            Notification::error ('No column mappings defined');
            $error = true;

        } else {
            $_POST['columns']['id'] = 'id';
            $match_csv = null;
            $match_db = null;

            foreach ($_POST['columns'] as $csv_name => $db_name) {
                if (isset($real_from_post[$csv_name])) {
                    $csv_name = $real_from_post[$csv_name];
                    if ($db_name == @$_POST['match_field']) {
                        $match_csv = $csv_name;
                        $match_db = $db_name;
                        break;
                    }
                }
            }

            if ((empty($match_db) or empty($match_csv)) and $_POST['duplicates'] != 'new') {
                Notification::error ('Field used for duplicate matching does not have a column mapping defined');
                $error = true;
            }
        }

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            $error = true;
        }

        if ($error) return false;

        Pdb::transact();

        $res = $this->_importPre();
        if (! $res) return false;

        while ($line = $csv->getNamedLine()) {
            // Ignore completely blank lines
            $blank = true;
            foreach ($line as $field) {
                if (trim($field)) {
                    $blank = false;
                    break;
                }
            }
            if ($blank) continue;

            // Look for a duplicate
            $is_duplicate = false;
            $existing_record = false;
            if ($_POST['duplicates'] != 'new') {
                Pdb::validateIdentifier($match_db);
                $q = "SELECT *
                    FROM ~{$this->table_name}
                    WHERE {$match_db} = ? ORDER BY id";
                try {
                    $existing_record = Pdb::q($q, [$line[$match_csv]], 'row');
                    $is_duplicate = true;
                } catch (RowMissingException $ex) {
                    // No problem
                }
            }

            // Prepare the field values
            $field_values = array();
            $new_data = array();
            foreach ($_POST['columns'] as $csv_name => $db_name) {
                if ($db_name == null) continue;
                if (!isset($real_from_post[$csv_name])) continue;

                $csv_name = $real_from_post[$csv_name];
                $new_data[$db_name] = trim($line[$csv_name]);
            }

            // Do pre-import processing
            $res = $this->_importPreRecord($new_data, $line);
            if (! $res) continue;

            // Prepare data for insert/update
            foreach ($new_data as $key => $val) {
                $field_values[$key] = $val;
            }

            // Kill off the id column
            if (! $this->import_id_column) {
                unset ($field_values['id']);
            }


            if ($is_duplicate) {
                // Has a duplicate record, do the appropriate action
                switch ($_POST['duplicates']) {
                    case 'new':
                        $field_values['date_added'] = Pdb::now();
                        $field_values['date_modified'] = Pdb::now();
                        $record_id = Pdb::insert($this->table_name, $field_values);
                        $type = 'insert';
                        break;

                    case 'merge_blank':
                        foreach ($field_values as $col => $val) {
                            if ($val == '' or $val == 'NULL' or $val == "''") {
                                unset ($field_values[$col]);
                            }
                        }
                        if (empty($field_values)) continue 2;
                        // fall-through

                    case 'merge':
                        $field_values['date_modified'] = Pdb::now();
                        Pdb::update($this->table_name, $field_values, ['id' => $existing_record['id']]);
                        $record_id = $existing_record['id'];
                        $type = 'update';
                        break;

                    case 'skip':
                        continue 2;

                }

            } else {
                // No dupe, just do an insert
                $field_values['date_added'] = Pdb::now();
                $field_values['date_modified'] = Pdb::now();
                $record_id = Pdb::insert($this->table_name, $field_values);
                $type = 'insert';
            }

            // Do post-import processing
            $res = $this->_importPostRecord($record_id, $new_data, $existing_record, $type, $line);

            $ai_config_fields = $_POST['multiedit_ai_fields'] ?? [];
            $activation = $_POST['activation_status'] ?? false;

            // Do post-import processing for AI handlers
            $res = $this->_importPostRecordAi($record_id, $new_data, $existing_record, $type, $line, $ai_config_fields, $activation);

            if (! $res) return false;
        }

        $res = $this->_importPost();
        if (! $res) return false;

        // Commit this here as our main import is finished.
        // An AI worker job will need records to be finalised in the DB
        Pdb::commit();

        // This by default will redirect to a worker job if AI is enabled
        $info = $this->_importPostAi();
        if ($info === false) return false;

        if (is_array($info)) {
            Notification::confirm('AI Background Job created.', 'plain', 'default', [$info['log_url'] => 'View AI progress']);
        }

        return true;
    }


    /**
    * Try to guess the database name for a given CSV heading.
    * If you can't figure it out, return NULL.
    * If NULL is returned, the rudimentry almost-exact guesser will be run.
    *
    * @param string $csv_heading The exact heading provided in the CSV file.
    * @return string|null The database field name to use. Must exactly match the database field name.
    **/
    protected function _importColGuess($csv_heading) { return null; }


    /**
    * Called when the import form is being built.
    *
    * Returns HTML of extra options to display, or null if no extra options.
    **/
    protected function _importExtraOptions () { return null; }


    /**
     * Called when the import form is being built.
     *
     * Returns HTML of extra options to display, or null if no extra options.
     *
     * @param array $headings The headings from the CSV file
     * @param array $db_columns The columns in the database table
     * @param array $data Optional form data
     *
     * @return string|null HTML of extra options to display, or null if no extra options.
     */
    protected function _importAiOptions ($headings, $db_columns, $data) {
        if (!AI::isEnabled()) {
            return null;
        }

        $view = new PhpView("sprout/admin/generic_import_ai");
        $view->headings = $headings;
        $view->db_columns = $db_columns;
        $view->data = $data;

        return $view->render();
    }


    /**
    * Called at the beginning of the the import process.
    * Is called from within a transaction.
    * Return FALSE to abort the import.
    **/
    protected function _importPre() { return true; }


    /**
    * Called after the field data has been determined, but before the insert or update is run.
    *
    * Return FALSE to skip the record.
    *
    * @param array $new_data The CSV data, with database-mapped names, but before
    *                        the database quoting has happened.
    *                        This is a by-reference argument.
    * @param array $raw_data Raw CSV data, with original field names.
    **/
    protected function _importPreRecord(&$new_data, $raw_data) { return true; }


    /**
    * Called after a record has been inserted or updated.
    *
    * @param int $record_id The id of the record that was inserted or updated.
    * @param array $new_data The new data of the record.
    * @param array $existing_record The old data of the record, which has now been replaced.
    * @param string $type One of 'insert' or 'update'
    * @param array $raw_data Raw CSV data, with original field names.
    *
    * @return boolean False if any errors are encountered; will cancel the entire import process.
    **/
    protected function _importPostRecord ($record_id, $new_data, $existing_record, $type, $raw_data) { return true; }


    /**
     * Called after a record has been inserted or updated.
     *
     * Some args are unused but are available through the import tool and may be used for custom tools
     *
     * Each $config_fields entry should look like the following:
     *
     * [ 'target_col' => 'col_name',
     *   'prompt_source' => 'db_col' or 'manual',
     *   'prompt_col' => 'col_name', // for db_col
     *   'prompt_text' => 'manual text', // for manual
     *   'ai_class' => 'class_name',
     *   'ai_method' => 'method_name'
     * ]
     *
     * @param int $record_id The id of the record that was inserted or updated.
     * @param array $new_data The new data of the record.
     * @param array $existing_record The old data of the record, which has now been replaced.
     * @param string $type One of 'insert' or 'update'
     * @param array $raw_data Raw CSV line or table row, with original field names.
     * @param array $ai_config_fields Array of fields for AI post processing. @see generic_import_ai.php
     * @param string $activation Activation status for the record @see table ai_content_queue::activation_status
     *
     * @return boolean False if any errors are encountered; will cancel the entire import process.
     */
    protected function _importPostRecordAi($record_id, $new_data, $existing_record, $type, $raw_data, $ai_config_fields, $activation)
    {
        if (!AI::isEnabled()) {
            return true;
        }

        if (empty($ai_config_fields)) {
            return true;
        }

        // Build a list of fields to process, with columns mapped as needed
        $ai_config['ai_fields'] = [];

        foreach ($ai_config_fields as &$ai_field) {
            $ai_field['prompt'] = match ($ai_field['prompt_source']) {
                'db_col' => $raw_data[$ai_field['prompt_col']],
                'manual' => $this->_buildManualAiPrompt($ai_field['prompt_text'], $raw_data),
                default => throw new Exception('Unknown prompt source'),
            };

            $ai_config['ai_fields'][] = $ai_field;
        }
        unset($ai_field);

        $data = [
            'target_table' => $this->table_name,
            'target_id' => $record_id,

            'status' => 'queued',
            'activation_status' => $activation,

            'date_added' => Pdb::now(),
            'date_modified' => Pdb::now(),
        ];

        // Simple counter for rows added
        $res = 0;

        foreach ($ai_config['ai_fields'] as $ai_field) {
            $data['target_col'] = $ai_field['target_col'];
            $data['prompt'] = $ai_field['prompt'];

            $data['class'] = $ai_field['ai_class'];
            $data['method'] = $ai_field['ai_method'];

            $res += Pdb::insert('ai_content_queue', $data);
        }

        // This is falsey if it all broke. We could maybe make this more granular if the need arises.
        return (bool) $res;
    }


    /**
     * Perform string replacements on prompt text, inserting raw data as needed.
     *
     * We replace content of the form '{{ field_name }}' with the field from raw data.
     *
     * This may be extended per controller for different or more complex use cases if needed.
     *
     * @param string $prompt_text
     * @param array $raw_data
     * @return string
     */
    protected function _buildManualAiPrompt($prompt_text, $raw_data)
    {
        $matches = [];
        preg_match_all('/{{\s*([a-z0-9_]+)\s*}}/i', $prompt_text, $matches);

        foreach ($matches[1] as $match) {
            $prompt_text = str_replace('{{ ' . $match . ' }}', $raw_data[$match], $prompt_text);
        }

        return $prompt_text;
    }


    /**
    * Called at the end of the the import process, after everything has been done.
    * Is called from within a transaction.
    * Return FALSE to abort the import.
    **/
    protected function _importPost() { return true; }


    /**
    * Returns a list of email reports
    **/
    public function _getEmailReports()
    {
        $friendly = strtolower($this->friendly_name);
        $report_view = new PhpView("sprout/admin/generic_email_report_list");

        $conditions = $params = [];
        $conditions['controller'] = $this->getControllerName();

        $where = Pdb::buildClause($conditions, $params);
        $q = "SELECT report.*, GROUP_CONCAT(recip.email) AS recipients
            FROM ~email_reports AS report
            INNER JOIN ~email_report_recipients AS recip ON recip.email_report_id = report.id
            WHERE {$where}
            GROUP BY report.id
            ORDER BY record_order, name";
        $items = Pdb::query($q, $params, 'arr');

        // Clean up fields which are too large and build the column list
        $cols = [
            'Name' => 'name',
            'Format' => 'format',
            'Recipients' => 'recipients'
        ];

        // Create the itemlist for the preview section
        if (count($items) == 0) {
            $friendly_html = Enc::html($friendly);
            $report_view->itemlist = "<p><i>No reports configured for {$friendly_html}.</i></p>";

        } else {
            $itemlist = new Itemlist();
            $itemlist->main_columns = $cols;
            $itemlist->items = $items;
            $itemlist->setActionsFunc(
                function ($row) {
                    $actions = [];
                    $actions[] = '<a class="button button-small" href="admin/edit/email_report/' . $row['id'] . '">Edit</a>';
                    $actions[] = '<a class="button button-small"  href="admin/delete/email_report/' . $row['id'] . '">Delete</a>';
                    $actions[] = '<a class="button button-small"  href="admin/email_report_send/' . $row['id'] . '">Send now</a>';
                    return implode(' ', $actions);
                }
            );
            $report_view->itemlist = $itemlist->render();
        }

        $controller = $this->controller_name;
        $report_view->add_url = "admin/email_report_add/{$controller}";

        return array(
            'title' => 'Email reports for ' . strtolower($this->friendly_name),
            'content' => $report_view->render(),
        );
    }


    /**
    * Returns form for creating email reports
    **/
    public function _addEmailReport()
    {
        $report_view = new PhpView("sprout/admin/generic_email_report_add");
        $report_view->controller_name = $this->controller_name;
        $report_view->friendly_name = $this->friendly_name;
        $report_view->filters = json_encode($_GET);

        // Build the refine bar, adding the 'category' field if required
        if ($this->refine_bar) {
            $report_view->refine = $this->refine_bar->get();
        }

        // Apply filter
        list($where, $params, $refine_fields) = $this->applyRefineFilter();
        $report_view->refine_fields = json_encode($refine_fields);

        // Query which gets three records for the preview
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        $q = $this->_getExportQuery($where) . ' LIMIT 3';
        $items = Pdb::q($q, $params, 'arr');

        // Clean up fields which are too large and build the column list
        $cols = array();
        $modifiers = $this->export_modifiers;
        foreach ($items as &$row) {
            if (count($cols) == 0) {
                foreach ($row as $key => $junk) {
                    if (isset($modifiers[$key]) and $modifiers[$key] === false) continue;
                    $cols[$key] = $key;
                }
            }

            foreach ($row as $key => &$val) {
                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key, $row);
                }
            }

            foreach ($row as $key => &$val) {
                if (empty($val)) continue;
                if (strlen($val) > 50) $val = substr($val, 0, 50) . '...';
            }
        }

        // Create the itemlist for the preview section
        if (count($items) == 0) {
            $report_view->itemlist = '<p><i>No records found which match the refinebar clauses specified.</i></p>';

        } else {
            $itemlist = new Itemlist();
            $itemlist->main_columns = $cols;
            $itemlist->items = $items;
            $report_view->itemlist = $itemlist->render();
        }


        return array(
            'title' => 'Email report for ' . strtolower($this->friendly_name),
            'content' => $report_view->render(),
        );
    }


    /**
     * Send an email report, using its DB record
     *
     * @param array $report
     *
     * @return bool Email send success
     */
    public function _sendEmailReport(array $report)
    {
        $filters = json_decode($report['filters'], true);
        if (!is_array($filters)) $filters = [];

        // Apply filter
        list($where, $params, $refine_fields) = $this->applyRefineFilter($filters);

        // Query which gets records for the send
        if ($this->main_where) $where = array_merge($where, $this->main_where);
        $where = implode(' AND ', $where);
        if ($where == '') $where = '1';

        $q = $this->_getExportQuery($where);
        $items = Pdb::q($q, $params, 'arr');

        $cols = array();
        $modifiers = $this->export_modifiers;
        foreach ($items as &$row) {
            if (count($cols) == 0) {
                foreach ($row as $key => $junk) {
                    if (isset($modifiers[$key]) and $modifiers[$key] === false) continue;
                    $cols[$key] = $key;
                }
            }

            foreach ($row as $key => &$val) {
                if (!empty($modifiers[$key])) {
                    if (is_string($modifiers[$key])) $modifiers[$key] = new $modifiers[$key]();
                    $val = $modifiers[$key]->modify($val, $key, $row);
                }

                if ($val === null) {
                    $val = '';
                }

                if (strlen($val) > 50) {
                    $val = substr($val, 0, 50) . '...';
                }
            }
            unset($val);
        }
        unset($row);

        $date = Pdb::now();
        $report_name = "{$report['name']}_{$date}";

        $mail = new Email();
        $mail->Subject = "Report: {$report['name']} | {$date}";

        $q = "SELECT name, email FROM ~email_report_recipients WHERE email_report_id = ?";
        $recipients = Pdb::query($q, [$report['id']], 'map');

        foreach ($recipients as $recipient_name => $recipient_email) {
            $mail->AddAddress($recipient_email, $recipient_name);
        }

        if (empty($items)) {
            $mail->SkinnedHTML("<p>Your automated report generated no data for this run.<p><p>Thank you.</p>");
            return $mail->send();
        }

        switch($report['format']) {
            case 'csv':
                $data = QueryTo::csv($items);
                $filename = "{$report_name}.csv";
                break;
            case 'xml':
                $data = QueryTo::xml($items);
                $filename = "{$report_name}.xml";
                break;
            default:
                throw new Exception('Invalid emailreport type');
        }

        $filename = File::filenameMakeSane($filename);
        $mail->addStringAttachment($data, $filename);

        $mail->SkinnedHTML("<p>Your automated report is attached to this email.<p><p>Thank you.</p>");
        return $mail->send();
    }


    /**
     * Called at the end of the the import process, after everything has been done.
     * Is called from within a transaction, specifically for AI tasks.
     * Return FALSE to abort the import.
     *
     * @return array|bool Array of worker job info if a job was created, false on error, true if nothing to do
     */
    protected function _importPostAi() {
        if (!AI::isEnabled()) {
            return true;
        }

        $q = "SELECT COUNT(*) FROM ~ai_content_queue WHERE target_table = ?";
        $count = Pdb::query($q, [$this->table_name], 'val');

        if (empty($count)) return true;

        try {
            $info = WorkerCtrl::start('Sprout\\Helpers\\AI\\WorkerAiContentProcess');
        } catch (WorkerJobException $ex) {
            Kohana::logException($ex);
            Notification::error('Unable to create background job to process AI Content.');
            return false;
        }

        return $info;
    }


    /**
     * Return the WHERE clause to use for a given key which is provided by the RefineBar
     * This must be called in the extending class if no clause can be determined,
     * i.e. return parent::_getRefineClause()
     *
     * Allows custom non-table clauses to be added.
     * Is only called for key names which begin with an underscore.
     * The base table is aliased to 'item'.
     *
     * @param string $key The key name, including underscore
     * @param string $val The value which is being refined.
     * @param array &$query_params Parameters to add to the query which will use the WHERE clause
     * @return string|null WHERE clause, e.g. "item.name LIKE CONCAT('%', ?, '%')", "item.status IN (?, ?, ?)"
     */
    protected function _getRefineClause($key, $val, array &$query_params)
    {
        $tags = [];
        $tagwhere = '';

        // Some extra logic for the tag search
        if ($key == '_all_tag' or $key == '_any_tag') {
            $tags = Tags::splitupTags($val);
            $tagwhere = implode(',', str_split(str_repeat('?', count($tags))));
        }

        $fixed_dates = [
            'YESTERDAY',
            'THIS_WEEK',
            'THIS_MONTH',
            'THIS_YEAR',
            'WEEK_TO_DATE',
            'MONTH_TO_DATE',
            'YEAR_TO_DATE',
        ];

        if (in_array($key, ['_date_added', '_date_modified']) and !in_array($val, $fixed_dates)) {
            @list($val, $interval) = preg_split('/\s+/', trim($val));
            $val = (int) $val;
            $valid_intervals = [
                'MICROSECOND',
                'SECOND',
                'MINUTE',
                'HOUR',
                'DAY',
                'WEEK',
                'MONTH',
                'QUARTER',
                'YEAR',
                'SECOND_MICROSECOND',
                'MINUTE_MICROSECOND',
                'MINUTE_SECOND',
                'HOUR_MICROSECOND',
                'HOUR_SECOND',
                'HOUR_MINUTE',
                'DAY_MICROSECOND',
                'DAY_SECOND',
                'DAY_MINUTE',
                'DAY_HOUR',
                'YEAR_MONTH',
            ];
            if (!in_array($interval, $valid_intervals)) {
                throw new InvalidArgumentException('Invalid interval');
            }
        }

        switch ($key) {
            case '_date_added':
            case '_date_modified':
                $key = substr($key, 1);
                if ($val == 'YESTERDAY') {
                    $yesterday = time() - 86400;
                    $query_params[] = date('Y-m-d 00:00:00', $yesterday);
                    $query_params[] = date('Y-m-d 23:59:59', $yesterday);
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'THIS_WEEK') {
                    $query_params[] = date('Y-m-d 00:00:00', strtotime('last monday'));
                    $query_params[] = date('Y-m-d 23:59:59', strtotime('next sunday'));
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'THIS_MONTH') {
                    $query_params[] = date('Y-m-01 00:00:00');
                    $query_params[] = date('Y-m-t 23:59:59');
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'THIS_YEAR') {
                    $query_params[] = date('Y-01-01 00:00:00');
                    $query_params[] = date('Y-12-31 23:59:59');
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'WEEK_TO_DATE') {
                    $query_params[] = date('Y-m-d 00:00:00', strtotime('last monday'));
                    $query_params[] = date('Y-m-d H:i:s');
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'MONTH_TO_DATE') {
                    $query_params[] = date('Y-m-01 00:00:00');
                    $query_params[] = date('Y-m-d H:i:s');
                    return "item.{$key} BETWEEN ? AND ?";

                } else if ($val == 'YEAR_TO_DATE') {
                    $query_params[] = date('Y-01-01 00:00:00');
                    $query_params[] = date('Y-m-d H:i:s');
                    return "item.{$key} BETWEEN ? AND ?";

                } else {
                    if (empty($interval)) throw new InvalidArgumentException('Invalid date interval');
                    $query_params[] = $val;
                    return "item.{$key} >= DATE_SUB(NOW(), INTERVAL ? {$interval})";
                }

            case '_all_tag':
                $query_params[] = $this->getTableName();
                $query_params = array_merge($query_params, $tags);
                return "(SELECT COUNT(id) FROM sprout_tags WHERE record_table = ? AND record_id = item.id AND name IN ({$tagwhere})) = " . count($tags);

            case '_any_tag':
                $query_params[] = $this->getTableName();
                $query_params = array_merge($query_params, $tags);
                return "(SELECT COUNT(id) FROM sprout_tags WHERE record_table = ? AND record_id = item.id AND name IN ({$tagwhere})) >= 1";

        }

        return null;
    }


    /**
    * Return HTML for a search form
    **/
    public function _getSearchForm()
    {
        $view = new PhpView("sprout/admin/generic_search");

        // Build the outer view
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->refine = $this->refine_bar;
        $view = $view->render();

        return array(
            'title' => 'Search ' . Enc::html($this->friendly_name),
            'content' => $view,
        );
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
        $list = [];

        // Tag button
        $list[] = [
            'url' => sprintf('SITE/admin/call/%s/postJsonMultiTag', $this->controller_name),
            'class' => 'selection-action multiple-add-tag button button-blue icon-before icon-add',
            'label' => 'Add tag',
        ];

        if (!$this->main_delete) return $list;

        // Delete button
        $list[] = [
            'url' => sprintf('SITE/admin/extra/%s/multi_delete', $this->controller_name),
            'class' => 'selection-action button button-red icon-before icon-delete',
            'label' => 'Delete',
        ];

        return $list;
    }


    /**
    * Returns the SQL query for use by the contents list.
    *
    * The query MUST NOT include a LIMIT clause.
    * The query MUST include a SQL_CALC_FOUND_ROWS clause.
    * The main table SHOULD be aliased to 'item'.
    *
    * @param string $where A where clause to use.
    *         Generated based on the specified refine options.
    * @param string $order An order clause to use.
    * @param array $params Params to bind to the query. These will be modified to include per-record permissions
    * @return string A SQL query.
    **/
    protected function _getContentsQuery($where, $order, &$params)
    {
        $joins = '';

        // Determine if per-record permissions used for this controller
        // If so, and there's at least one per-record restriction,
        // ensure that records which the user can't access aren't displayed
        $restrict = PerRecordPerms::controllerRestricted($this);

        if ($restrict) {
            $has_record_perms = PerRecordPerms::hasRecordPerms($this);

            if ($has_record_perms) {
                array_unshift($params, $this->controller_name);
                $joins = "LEFT JOIN ~per_record_permissions AS rec_perm
                    ON rec_perm.controller = ? AND item.id = rec_perm.item_id";

                $cat_clause = PerRecordPerms::getCategoryClause();
                $cat_clause = substr($cat_clause, 1, -1);       // nuke leading and trailing brackets

                $where .= " AND (operator_categories IS NULL OR {$cat_clause})";
            }
        }

        $q = "SELECT SQL_CALC_FOUND_ROWS item.*
            FROM ~{$this->table_name} AS item
            {$joins}
            WHERE {$where}
            ORDER BY {$order}";
        return $q;
    }


    /**
    * Return HTML which represents a list of records for this controller
    **/
    public function _getContents()
    {
        if (empty($_GET['page'])) $_GET['page'] = 1;
        $_GET['page'] = (int) $_GET['page'];

        // Apply filter
        list($where, $params) = $this->applyRefineFilter();

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

        } else {
            $order = $this->main_order;
            preg_match('/(item\.)?([_a-z]+)( asc| desc)?/i', $this->main_order, $matches);
            $_GET['order'] = trim($matches[2]);
            $_GET['dir'] = trim(isset($matches[3]) ? strtolower($matches[3]) : 'asc');
        }

        // Get the actual records
        $offset = $this->records_per_page * ($_GET['page'] - 1);
        $q = $this->_getContentsQuery($where, $order, $params);
        $q .= " LIMIT {$this->records_per_page} OFFSET {$offset}";
        $items = Pdb::q($q, $params, 'arr');

        // Get the total number of records
        $q = "SELECT FOUND_ROWS() AS C";
        $total_row_count = (int) Pdb::q($q, [], 'val');

        // If no mode set, use the session
        // If a mode is set and valid, save in the session
        if (empty($_GET['main_mode'])) {
            $_GET['main_mode'] = @$_SESSION['admin'][$this->controller_name]['main_mode'];
        } else if ($this->main_modes[$_GET['main_mode']]) {
            $_SESSION['admin'][$this->controller_name]['main_mode'] = $_GET['main_mode'];
        }

        // If no valid mode set, use a default
        if (!isset($this->main_modes[$_GET['main_mode']])) {
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
            $items_view = $this->_getContentsView($items, $_GET['main_mode'], null);
        }

        // Build the pagination bar
        $paginate = $this->_paginationBar($_GET['page'], $total_row_count);

        return array(
            'title' => Enc::html($this->friendly_name),
            'content' => $refine . $mode_sel . $paginate . $items_view . $paginate,
        );
    }


    /**
    * Return HTML for a resultset of items
    * The returned HTML will be sandwiched between the refinebar and the pagination bar.
    *
    * @param Traversable $items The items to render.
    * @param string $mode The mode of the display.
    * @param mixed $unused anything $unused Not used in this controller, but used by has_categories
    **/
    public function _getContentsView($items, $mode, $unused)
    {
        return $this->_getContentsViewList($items, $unused);
    }


    /**
    * Formats a resultset of items into an Itemlist
    *
    * @param Traversable $items The items to render.
    * @param mixed $unused anything $unused Not used in this controller, but used by has_categories
    **/
    public function _getContentsViewList($items, $unused)
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
        $outer->itemlist = $itemlist->render();
        $outer->allow_add = $this->main_add;
        $outer->allow_del = $this->main_delete;

        return $outer->render();
    }


    /**
    * Builds the HTML for showing the navigation through pages in the admin.
    * This method is FINAL to help keep the user interface consistent.
    *
    * @param int $current_page The current page. 1-based index.
    * @param int $total_row_count The total number of records in the dataset.
    * @return string HTML for the paginate bar.
    **/
    final protected function _paginationBar($current_page, $total_row_count) {
        $total_page_count = ceil($total_row_count / $this->records_per_page);
        $prev_url = null;
        $next_url = null;

        if ($current_page > 1) $prev_url = sprintf('%spage=%u', Url::withoutArgs('page'), $current_page - 1);
        if ($current_page < $total_page_count) $next_url = sprintf('%spage=%u',  Url::withoutArgs('page'), $current_page + 1);

        $view = new PhpView('sprout/admin/pagination');
        $view->total_records = $total_row_count;
        $view->prev_url = $prev_url;
        $view->next_url = $next_url;
        $view->current_page = $current_page;
        $view->total_pages = $total_page_count ? $total_page_count : 1;

        return $view->render();
    }


    /**
    * Returns HTML for a ui component to update the current main view mode
    **/
    final protected function _modeSelector($current_mode) {
        $base = Url::withoutArgs('main_mode');

        ob_start();
        echo '<div class="mode-selector">';

        foreach ($this->main_modes as $key => $val) {
            if ($key == $current_mode) {
                echo '<a href="', $base, 'main_mode=', $key, '" class="button button-orange button-regular button-icon icon-before';
            } else {
                echo '<a href="', $base, 'main_mode=', $key, '" class="button button-grey button-regular button-icon icon-before';
            }

            if (is_array($val)) {
                list ($label, $icon) = $val;

                // Set the icon using the icon font class
                if ($icon === "list") {
                    $icon = "view_list";
                } else if ($icon === "grid") {
                    $icon = "view_module";
                }

                echo ' icon-', $icon, '" title="', Enc::html($label), '"><span class="-vis-hidden">' . Enc::html($label) . "</span>";

            } else {
                // Not an array? assume no icon
                echo '"><span>' . Enc::html($val) . '</span>';
            }

            echo '</a>';
        }

        echo '</div>';
        return ob_get_clean();
    }


    /**
     * Returns a page title and HTML for a form to add a record
     * @return array|AdminError Two elements: 'title' and 'content'
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
     * Is the "add" action saved?
     * These may be false if the UI provides its own save mechanism (e.g. multi-add)
     *
     * @return bool True if they are saved, false if they are not
     */
    public function _isAddSaved()
    {
        return true;
    }


    /**
     * Optional custom HTML for the save box
     * Return NULL to use the default HTML
     *
     * @return string|null HTML
     */
    public function _getCustomAddSaveHTML()
    {
        return null;
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
        return [
            'active' => 'Active',
        ];
    }


    /**
     * Inject the visiblity fields into a loaded json configuration, so they actually save
     *
     * @param array $conf JSON add/edit configuration
     */
    protected function injectVisiblityFields(array &$conf)
    {
        $conf['_visibility'] = [];

        $visibility = $this->_getVisibilityFields();
        foreach ($visibility as $name => $label) {
            $conf['_visibility'][] = ['field' => [
                'name' => $name,
                'label' => $label,
            ]];
        }

        if ($this->per_subsite) {
            $conf['_visibility'][] = ['field' => [
                'name' => 'subsite_id',
                'label' => 'Subsite',
                'empty' => null,
            ]];
        }
    }


    /**
     * Return the sub-actions for adding a record (e.g. preview)
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @return array
     */
    public function _getAddSubActions()
    {
        return [];
    }


    /**
    * Hook called by _getAddForm() just before the view is rendered
    *
    * @tag api
    * @tag module-api
    **/
    protected function _addPreRender($view) {}


    protected function _preSave($id, &$data)
    {
        if ($id == 0) {
            $data['date_added'] = Pdb::now();
        }
        $data['date_modified'] = Pdb::now();
    }

    /**
     * Process the saving of an add.
     *
     * @param int $item_id The new record id should be returned in this variable
     * @return boolean True on success, false on failure
     */
    public function _addSave(&$item_id)
    {

        // Auto-process form using JSON config
        $conf = $this->loadEditJson();
        $this->injectVisiblityFields($conf);
        return $this->saveJsonData($conf, $item_id);
    }


    /**
     * Fetch the record with a given id
     *
     * @param int $id Record to fetch
     * @return array Database row
     */
    protected function _getRecord($id)
    {
        return Pdb::get($this->table_name, $id);
    }


    /**
     * Returns a page title and HTML for a form to edit a record
     *
     * @param int $id The id of the record to get the edit form of
     * @return array|AdminError Two elements, 'title' and 'content'
     */
    public function _getEditForm($id)
    {
        $id = (int) $id;
        if ($id <= 0) throw new InvalidArgumentException('$id must be greater than 0');

        // Get the item
        try {
            $item = $this->_getRecord($id);
            $data = $item;
        } catch (RowMissingException $ex) {
            $single = Inflector::singular($this->friendly_name);
            return new AdminError("Invalid id specified - {$single} does not exist");
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
            $data = $_SESSION['admin']['field_values'];
            unset($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;
        $view->data = $data;
        $view->errors = $errors;

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
     * Is the "edit" action saved?
     * These may be false if the UI provides its own save mechanism
     *
     * @return bool True if they are saved, false if they are not
     */
    public function _isEditSaved($item_id)
    {
        return true;
    }


    /**
     * Optional custom HTML for the save box
     * Return NULL to use the default HTML
     *
     * @param int $item_id Record which is being edited
     * @return string|null HTML
     */
    public function _getCustomEditSaveHTML($item_id)
    {
        return null;
    }


    /**
     * Return the sub-actions for editing a record (e.g. deleting)
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @return array Each key is a unique reference to the action, e.g. 'delete', and the value is an array, with keys:
     *         url => URL to link to, e.g. "admin/delete/thing/$item_id"
     *         name => Label to display to the user, e.g. 'Delete'
     *         class => CSS class(es) for the icon, e.g. 'icon-link-button icon-before icon-delete'
     *         new_tab => True to show in new window/tab (optional; defaults to false)
     */
    public function _getEditSubActions($item_id)
    {
        $actions = [];

        if ($this->_isDeleteSaved($item_id)) {
            $actions['delete'] = [
                'url' => 'admin/delete/' . $this->controller_name . '/' . $item_id,
                'name' => 'Delete',
                'class' => 'icon-link-button icon-before icon-delete',
            ];
        }

        return $actions;
    }


    /**
     * Return the URL to use for the 'view live site' button, when editing a given record
     *
     * @param int $item_id Record which is being edited
     * @return string|null URL, either absolute or relative. null if default url should be used
     */
    public function _getEditLiveUrl($item_id)
    {
        return null;
    }


    /**
    * Hook called by _getEditForm() just before the view is rendered
    *
    * @tag api
    * @tag module-api
    **/
    protected function _editPreRender($view, $item_id) {}


    /**
     * Process the saving of a record.
     *
     * @param int $item_id The ID of the record to save the data into
     * @return boolean True on success, false on failure
     */
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;
        if ($item_id <= 0) throw new InvalidArgumentException('$item_id must be greater than 0');

        // Auto-process form using JSON config
        $conf = $this->loadEditJson();
        $this->injectVisiblityFields($conf);
        return $this->saveJsonData($conf, $item_id);
    }


    /**
     * Optional custom HTML for the save box
     * Return NULL to use the default HTML
     *
     * @param int $item_id The row ID of the record being duplicated
     *
     * @return string HTML
     */
    public function _getCustomDuplicateSaveHTML(int $item_id)
    {
        return '';
    }


    /**
     * Return the sub-actions for duplicating a record
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @param int $item_id The row ID of the record being duplicated
     *
     * @return array
     */
    public function _getDuplicateSubActions(int $item_id)
    {
        return [];
    }


    /**
    * Return HTML which represents the form for duplicating a record
    *
    * @param int $id The id of the record to get the original data from
    **/
    public function _getDuplicateForm(int $id)
    {
        if ($id <= 0) throw new InvalidArgumentException('$id must be greater than 0');

        // Get the item
        $data = $item = $this->_getRecord($id);

        // Clobber duplication fields with any defaults defined in controller
        if (@count($this->duplicate_defaults)) {
            foreach ($this->duplicate_defaults as $key => $val) {
                $data[$key] = $val;
            }
        }

        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset ($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
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

        // Remove data from any joiner table multiedits as specified in the controller
        $omit_tables = $this->duplicate_omit_table_joints ?? [];
        foreach ($omit_tables as $omit_table) {
            if (isset($data["multiedit_{$omit_table}"])) {
                $data["multiedit_{$omit_table}"] = [];
            }
        }

        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;
        $view->item = $item;
        $view->data = $data;
        $view->errors = $errors;

        $this->_duplicatePreRender($view, $id);

        $title = 'Duplicating ' . Enc::html(Inflector::singular($this->friendly_name));
        return array(
            'title' => $title . ' <strong>' . Enc::html($this->_identifier($item)) . '</strong>',
            'content' => $view->render()
        );
    }


    /**
    * Hook called by _getDuplicateForm() just before the view is rendered
    *
    * @param BaseView $view The view which will be rendered
    * @param int $item_id The id of the record to get the original data from
    *
    * @tag api
    * @tag module-api
    **/
    protected function _duplicatePreRender($view, int $item_id)
    {
        $this->_editPreRender($view, $item_id);
    }


    /**
    * Process the saving of a duplication. Basic version just calls _editSave
    *
    * @param int $id The record to save
    *
    * @return boolean True on success, false on failure
    **/
    public function _duplicateSave(int $id)
    {
        return $this->_editSave($id);
    }


    /**
    * Return HTML which represents the form for deleting a record
    *
    * @param int $id The record to show the delete form for
    *
    * @return string|array|AdminError The HTML code which represents the edit form
    **/
    public function _getDeleteForm($id)
    {
        $id = (int) $id;

        try {
            $view = new PhpView("{$this->getModulePath()}/admin/{$this->controller_name}_delete");
        } catch (FileMissingException $ex) {
            $view = new PhpView("sprout/admin/generic_delete");
        }
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->id = $id;

        // Load item details
        try {
            $view->item = $this->_getRecord($id);
        } catch (RowMissingException $ex) {
            return [
                'title' => 'Error',
                'content' => "Invalid id specified - {$this->controller_name} does not exist",
            ];
        }

        return array(
            'title' => 'Deleting ' . Enc::html(Inflector::singular($this->friendly_name)) . ' <strong>' . Enc::html($this->_identifier($view->item)) . '</strong>',
            'content' => $view->render()
        );
    }


    /**
     * Check if deletion of a particular record is allowed
     * This method may be overridden if ignoring the $main_delete property is desired
     *
     * @param int $item_id The ID of the target record row
     *
     * @return bool True if they are saved, false if they are not
     */
    public function _isDeleteSaved(int $item_id)
    {
        return true;
    }


    /**
     * Return the sub-actions for deleting a record (e.g. cancel)
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @param int $item_id The ID of the target record row
     *
     * @return array
     */
    public function _getDeleteSubActions(int $item_id)
    {
        $actions = [];

        $actions['cancel'] = [
            'url' => 'admin/edit/' . $this->controller_name . '/' . $item_id,
            'name' => 'Cancel',
        ];

        return $actions;
    }


    /**
     * Does custom actions before _deleteSave method is called, e.g. extra security checks
     *
     * @param int $item_id The record to delete
     *
     * @return void
     *
     * @throws Exception if the deletion shouldn't proceed for some reason
     */
    public function _deletePreSave($item_id)
    {
    }


    /**
     * Does custom actions after the _deleteSave method is called, e.g. clearing cache
     *
     * @param int $item_id The record to delete
     *
     * @return void
     */
    public function _deletePostSave($item_id)
    {
    }


    /**
     * Deletes an item and logs the deleted data
     *
     * @param int $item_id The record to delete
     *
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
    * This is called after every add, edit and delete, as well as other (i.e. bulk) actions.
    * Use it to clear any frontend caches. The default is an empty method.
    *
    * @param string $action The name of the action (e.g. 'add', 'edit', 'delete', etc)
    * @param int $item_id The item which was affected. Bulk actions (e.g. reorders) will have this set to NULL.
    **/
    public function _invalidateCaches($action, $item_id = null) {}


    /**
    * Return the navigation for this controller
    * Should return HTML
    **/
    abstract public function _getNavigation();


    /**
     * Function to determine if we log admin actions for this controller
     *
     * @return bool
     */
    public function _actionLog()
    {
        return $this->action_log;
    }


    /**
     * Function to determine if we allow email reports to be created
     *
     * @return bool
     */
    public function _emailReports()
    {
        return $this->email_reports;
    }


    /**
    * Returns tools to show in the left hand navigation. Return an empty array if no tools.
    **/
    public function _getTools()
    {
        $friendly = Enc::html(strtolower($this->friendly_name));

        $tools = array();
        $tools['import'] = "<li class=\"import\"><a href=\"SITE/admin/import_upload/{$this->controller_name}\">Import {$friendly}</a></li>";
        $tools['export'] = "<li class=\"export\"><a href=\"SITE/admin/export/{$this->controller_name}\">Export {$friendly}</a></li>";

        if (AI::isEnabled()) {
            $tools['ai_reprocess'] = "<li class=\"export\"><a href=\"SITE/admin/ai_reprocess/{$this->controller_name}\">AI bulk content editor</a></li>";
        }

        if ($this->_actionLog()) {
            $tools['action_log'] = '<li class="action-log"><a href="SITE/admin/contents/action_log?record_table=' . $this->getTableName() . '">View action log</a></li>';
        }

        if ($this->_emailReports()) {
            $tools['email_reports'] = '<li class="action-log"><a href="SITE/admin/email_reports/' . $this->getControllerName() . '">Automated email reports</a></li>';
        }

        return $tools;
    }


    /**
     * Creates the identifier used in the heading, and for reordering.
     * @param array $item The row being viewed/edited/etc.
     * @return string
     */
    public function _identifier(array $item)
    {
        if (isset($item['name'])) return $item['name'];
        if (isset($item['id'])) return "#{$item['id']}";
        return '';
    }


    // This may even be a really bad idea, I haven't decided yet
    /* Optional: _extra_<command>($id) */


    /**
    * Form to delete multiple records
    **/
    public function _extraMultiDelete()
    {
        if (! AdminPerms::controllerAccess($this->getControllerName(), 'delete')) {
            return new AdminError('Access denied');
        }

        if (empty($_GET['ids'])) {
            Notification::error('No items selected for deletion');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        $view = new PhpView('sprout/admin/categories_multi_delete');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->ids = $_GET['ids'];

        return $view;
    }

    /**
    * Delete multiple records
    **/
    public function postMultiDelete()
    {
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'delete')) {
            Notification::error('Access denied');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        if (empty($_POST['ids'])) {
            Notification::error('No items selected for deletion');
            Url::redirect('admin/contents/' . $this->controller_name);
        }

        $success = 0;
        $constraint = 0;
        foreach ($_POST['ids'] as $item_id) {
            try {
                $res = $this->_deleteSave($item_id);
                if ($res) {
                    $success++;
                }
            } catch (ConstraintQueryException $ex) {
                if (Pdb::inTransaction()) {
                    Pdb::rollback();
                }
                $constraint++;
            }
        }

        $this->_invalidateCaches('multi_delete');

        if ($success > 0) {
            Notification::confirm('Deletion of ' . $success . ' ' . Inflector::singular($this->getFriendlyName(), $success) . ' was successful');
        }
        if ($constraint > 0) {
            Notification::error($constraint . ' ' . Inflector::singular($this->getFriendlyName(), $constraint) . " in use by other modules and can't be deleted");
        }

        Url::redirect('admin/contents/' . $this->controller_name);
    }


    /**
    * Multi-tag some items. Uses AJAX. Returns JSON.
    **/
    public function postJsonMultiTag()
    {
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess($this->getControllerName(), 'edit')) {
            Json::error('Access denied');
        }

        if (empty($_POST['ids'])) {
            Json::error('No items selected for tagging');
        }

        $_POST['tags'] = trim($_POST['tags']);
        if ($_POST['tags'] == '') {
            Json::error('No tags entered');
        }

        $new_tags = Tags::splitupTags($_POST['tags']);
        foreach ($_POST['ids'] as $item_id) {
            Tags::update($this->table_name, $item_id, $new_tags, false);
        }

        $this->_invalidateCaches('multi_edit');

        Json::confirm();
    }


    /**
     * Return list of records for given search term
     * Used for Fb::autocomplete
     *
     * @return void Echos JSON directly
     */
    public function ajaxLookup()
    {
        AdminAuth::checkLogin();

        if (!empty($_GET['id'])) {
            $q = "SELECT name AS label FROM ~{$this->table_name} WHERE id = ?";
            $records = Pdb::query($q, [$_GET['id']], 'arr');
            Json::out($records);
        }

        $q = "SELECT id, name AS value FROM ~{$this->table_name} WHERE name LIKE CONCAT('%', ?, '%')";
        $records = Pdb::query($q, [Pdb::likeEscape($_GET['term'])], 'arr');
        Json::out($records);
    }


    /**
     * Return list of records for given search term
     * Used for Fb::autocompleteList
     *
     * @return void Echos JSON directly
     */
    public function ajaxLookupList()
    {
        AdminAuth::checkLogin();

        if (!empty($_GET['ids'])) {
            $conditions = [];
            $params = [];

            $conditions[] = ['id', 'IN', explode(',', $_GET['ids'])];

            $where = Pdb::buildClause($conditions, $params);

            $q = "SELECT id, name AS label FROM ~{$this->table_name} WHERE {$where}";
            $records = Pdb::query($q, $params, 'arr');
            Json::out($records);
        }

        $q = "SELECT id, name AS value FROM ~{$this->table_name} WHERE name LIKE CONCAT('%', ?, '%')";
        $records = Pdb::query($q, [Pdb::likeEscape($_GET['term'])], 'arr');
        Json::out($records);
    }
}
