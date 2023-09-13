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
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
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
    * This is the name of the controller - should match the class name, but without the '_Controller' bit.
    **/
    protected $controller_name;

    /**
    * This is the friendly name of the controller. In 99% of cases, should be the plural form of the controller name
    **/
    protected $friendly_name;

    /**
    * The friendly name used in the sidebar navigation. Defaults to matching the friendly name.
    **/
    protected $navigation_name;

    /**
    * This is the name of the table to get data from. Will be automatically deducted from the controller name if not specified
    **/
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
        if ($this->controller_name == '') throw new Exception ('Managed controller without a defined name!');
        if ($this->friendly_name == '') throw new Exception ('Managed controller without a defined friendly name!');

        if ($this->navigation_name == '') $this->navigation_name = $this->friendly_name;

        if ($this->main_columns) {
            foreach ($this->main_columns as $col) {
                if ($col === 'name') {
                    if (!$this->main_columns) $this->main_columns = array('Name' => 'name');
                    if (!$this->import_columns) $this->import_columns = array('name');
                    break;
                }
            }
        }

        $this->initTableName();
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
     * @return void
     */
    protected function initTableName()
    {
        if ($this->table_name) return;
        $this->table_name = Inflector::plural($this->controller_name);
    }


    /**
    * Returns the defined controller name.
    **/
    final public function getControllerName() {
        return $this->controller_name;
    }

    /**
    * Returns the defined controller friendly name
    **/
    final public function getFriendlyName() {
        return $this->friendly_name;
    }

    /**
    * Returns the defined controller navigation name
    **/
    final public function getNavigationName() {
        return $this->navigation_name;
    }

    /**
    * Returns the defined table name
    **/
    final public function getTableName() {
        return $this->table_name;
    }

    /**
    * Gets the name of the controller to use for the top nav
    **/
    public function getTopnavName()
    {
        return $this->controller_name;
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
    * Returns the intro HTML for this controller.
    **/
    public function _intro()
    {
        Url::redirect('admin/contents/' . $this->controller_name);
    }


    /**
    * Returns the SQL query for use by the export tools.
    * The query does MUST NOT include a LIMIT clause.
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
     * Applies filters defined in the query string using a LIKE contains
     * Only fields which exist in the RefineBar will be filtered
     * @param array $source_data Source data, e.g. $_GET or $_POST
     * @return array Three elements:
     *         [0] (array) WHERE clauses, to be joined by the calling code with AND
     *         [1] (array) Params to use in a Pdb::q call which uses the generated WHERE clauses
     *         [2] (array) Key-value pairs containing filter options extracted from the $_GET data
     */
    protected function applyRefineFilter(array $source_data = null)
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
        list($where, $params, $export->refine_fields) = $this->applyRefineFilter();

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
                if (strlen($val) > 50) $val = substr($val, 0, 50) . '...';
            }
        }

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
            'title' => 'Export ' . Enc::html(strtolower($this->friendly_name)),
            'content' => $export->render(),
        );
    }


    /**
    * Does the actual export. Return false on error.
    *
    * @return array [
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

                return array('type' => 'text/csv; charset=UTF-8', 'filename' => $filename . '.csv', 'data' => $data);


            case 'xml':
                $data = QueryTo::xml($res, $this->export_modifiers);
                if (! $data) return false;

                return array('type' => 'application/xml', 'filename' => $filename . '.xml', 'data' => $data);
        }

        // Is closed by QueryTo::csv, but remains open otherwise
        $res->closeCursor();

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

        $title = 'Import ' . Enc::html(strtolower($this->friendly_name));

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

            if (!$match_csv and $_POST['duplicates'] != 'new') {
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
            if (! $res) return false;
        }

        $res = $this->_importPost();
        if (! $res) return false;

        Pdb::commit();

        return true;
    }


    /**
    * Try to guess the database name for a given CSV heading.
    * If you can't figure it out, return NULL.
    * If NULL is returned, the rudimentry almost-exact guesser will be run.
    *
    * @param string $csv_heading The exact heading provided in the CSV file.
    * @return string The database field name to use. Must exactly match the database field name.
    **/
    protected function _importColGuess($csv_heading) { return null; }


    /**
    * Called when the import form is being built.
    *
    * Returns HTML of extra options to display, or null if no extra options.
    **/
    protected function _importExtraOptions () { return null; }


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
    * @return boolean False if any errors are encountered; will cancel the entire import process.
    **/
    protected function _importPostRecord ($record_id, $new_data, $existing_record, $type, $raw_data) { return true; }


    /**
    * Called at the end of the the import process, after everything has been done.
    * Is called from within a transaction.
    * Return FALSE to abort the import.
    **/
    protected function _importPost() { return true; }


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
     * @return string WHERE clause, e.g. "item.name LIKE CONCAT('%', ?, '%')", "item.status IN (?, ?, ?)"
     */
    protected function _getRefineClause($key, $val, array &$query_params)
    {

        // Some extra logic for the tag search
        if ($key == '_all_tag' or $key == '_any_tag') {
            $tags = Tags::splitupTags($val);
            $tagwhere = implode(',', str_split(str_repeat('?', count($tags))));
        }

        if (in_array($key, ['_date_added', '_date_modified']) and $val != 'YESTERDAY') {
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
            case '_date_modified':
                if ($val == 'YESTERDAY') {
                    $yesterday = time() - 86400;
                    $start = date('Y-m-d 00:00:00', $yesterday);
                    $end = date('Y-m-d 23:59:59', $yesterday);
                    return "item.date_added BETWEEN '{$start}' AND '{$end}'";
                } else {
                    $query_params[] = $val;
                    return "item.date_modified >= DATE_SUB(NOW(), INTERVAL ? {$interval})";
                }

            case '_date_added':
                if ($val == 'YESTERDAY') {
                    $yesterday = time() - 86400;
                    $start = date('Y-m-d 00:00:00', $yesterday);
                    $end = date('Y-m-d 23:59:59', $yesterday);
                    return "item.date_added BETWEEN '{$start}' AND '{$end}'";
                } else {
                    $query_params[] = $val;
                    return "item.date_added >= DATE_SUB(NOW(), INTERVAL ? {$interval})";
                }

            case '_all_tag':
                $query_params[] = $tbl;
                $query_params = array_merge($query_params, $tags);
                return "(SELECT COUNT(id) FROM sprout_tags WHERE record_table = ? AND record_id = item.id AND name IN ({$tagwhere})) = " . count($tags);

            case '_any_tag':
                $query_params[] = $tbl;
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
        if (!empty($_GET['order'])) {
            Pdb::validateIdentifier($_GET['order']);
            $order = "item.{$_GET['order']}";
            if (@$_GET['dir'] == 'asc' or @$_GET['dir'] == 'desc') {
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
        $total_row_count = Pdb::q($q, [], 'val');

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
    * @param anything $unused Not used in this controller, but used by has_categories
    **/
    public function _getContentsView($items, $mode, $unused)
    {
        return $this->_getContentsViewList($items, $unused);
    }


    /**
    * Formats a resultset of items into an Itemlist
    *
    * @param Traversable $items The items to render.
    * @param anything $unused Not used in this controller, but used by has_categories
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
    * @param $current_page The current page. 1-based index.
    * @param $total_row_count The total number of records in the dataset.
    * @return HTML for the paginate bar.
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
     * @param return string HTML
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
     * @return array Two elements, 'title' and 'content'
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
     * @param return string HTML
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
     * @param int $item_id Record which is being editied
     * @return string URL, either absolute or relative
     * @return null Default url should be used
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
     * @param return string HTML
     */
    public function _getCustomDuplicateSaveHTML($item_id)
    {
        return null;
    }


    /**
     * Return the sub-actions for duplicating a record
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @return array
     */
    public function _getDuplicateSubActions($item_id)
    {
        return [];
    }


    /**
    * Return HTML which represents the form for duplicating a record
    *
    * @param int $id The id of the record to get the original data from
    **/
    public function _getDuplicateForm($id)
    {
        $id = (int) $id;
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
    * @tag api
    * @tag module-api
    **/
    protected function _duplicatePreRender($view, $item_id)
    {
        $this->_editPreRender($view, $item_id);
    }


    /**
    * Process the saving of a duplication. Basic version just calls _editSave
    *
    * @param int $id The record to save
    * @return boolean True on success, false on failure
    **/
    public function _duplicateSave($id)
    {
        return $this->_editSave($id);
    }


    /**
    * Return HTML which represents the form for deleting a record
    *
    * @param int $id The record to show the delete form for
    * @return string The HTML code which represents the edit form
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
     * @param int $item_id
     * @return bool True if they are saved, false if they are not
     */
    public function _isDeleteSaved($item_id)
    {
        return true;
    }


    /**
     * Return the sub-actions for deleting a record (e.g. cancel)
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @return array
     */
    public function _getDeleteSubActions($item_id)
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
     * @param int $item_id The record to delete
     * @return void
     * @throws Exception if the deletion shouldn't proceed for some reason
     */
    public function _deletePreSave($item_id)
    {
    }


    /**
     * Does custom actions after the _deleteSave method is called, e.g. clearing cache data
     * @param int $item_id The record to delete
     * @return void
     */
    public function _deletePostSave($item_id)
    {
    }


    /**
     * Deletes an item and logs the deleted data
     * @param int $item_id The record to delete
     * @param bool True on success, false on failure
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


    public function _actionLog()
    {
        return $this->action_log;
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

        if ($this->_actionLog()) {
            $tools['action_log'] = '<li class="action-log"><a href="SITE/admin/contents/action_log?record_table=' . $this->getTableName() . '">View action log</a></li>';
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
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws QueryException
     * @throws ConnectionException
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
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws QueryException
     * @throws ConnectionException
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
