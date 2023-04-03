<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Controllers;

use Exception;
use InvalidArgumentException;

use karmabunny\kb\Uuid;

use Sprout\Controllers\Admin\ManagedAdminController;
use Sprout\Exceptions\FileMissingException;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Models\PdbForeignKey;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\JsonForm;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Profiling;
use Sprout\Helpers\Request;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Text;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;


/**
 * Kohana Controller class. The controller class must be extended to work
 * properly, so this class is defined as abstract.
 */
abstract class Controller extends BaseController
{

    /** Should this controller log add/edit/delete actions? */
    protected $action_log = false;


    /** @inheritdoc */
    public function _run($method, $args)
    {
        $class = static::class;
        Profiling::begin($method, $class, ['args' => $args]);

        register_shutdown_function(function() use ($method, $class) {
            Profiling::end($method, $class);
        });

        return parent::_run($method, $args);
    }


    /**
     * Stores a history item in the database, recording an add (i.e. insert), edit, or delete.
     * Should be called AFTER the action has been made.
     * N.B. This is a low-level method; the friendlier wrapper methods are preferred;
     * e.g. {@see Controller::logAdd}, {@see Controller::logEdit}
     *
     * @param string $table The table which had data modified by the query
     * @param int $record_id The id of the added/edited/deleted record
     * @param int $type ENUM value from history_items.type
     * @param array $data Data associated with an add/edit.
     *        The handling differs based on the $type parameter:
     *        'Add' ignored
     *        'Edit' should be an array of the pre-update data, used to build a diff
     *        'Delete' should contain the complete data of the deleted record
     *        'Add category' should contain ['cat_id' => value]
     *        'Delete category' should contain ['cat_id' => value]
     *        'Change password' should be an empty array
     * @param int $parent_log_id ID of a parent log entry in the history_items table.
     *        This is used when multiple records are deleted by a single action.
     *        Undoing that action can then restore all of the deleted data.
     *        N.B. This is only relevant for deletes
     * @return int ID of the record inserted into the history_items table;
     *         0 if no insert was done; -1 if it failed
     * @throws QueryException
     */
    protected function logAction($table, $record_id, $type, array $data = [], $parent_log_id = 0)
    {
        static $types = null;
        static $insert_query = null;
        static $pdo = null;

        if (!$this->action_log) return 0;

        $record_id = (int) $record_id;
        $parent_log_id = (int) $parent_log_id;
        if (!$types) $types = Pdb::extractEnumArr('history_items', 'type');

        if (!in_array($type, $types)) {
            throw new InvalidArgumentException('Invalid action type: ' . $type);
        }

        switch ($type) {
            case 'Add':
                $data = $this->loadRecord($table, $record_id);
                if (!$data) return 0;
                break;

            case 'Edit':
                $row = $this->loadRecord($table, $record_id);
                if (!$row) return 0;
                unset ($row['date_modified']);

                foreach ($row as $name => $val) {
                    if ($data[$name] == $val) {
                        unset ($row[$name]);
                        continue;
                    }

                    $row[$name] = trim(strip_tags($row[$name]));
                    $row[$name] = str_replace("\n", ' ', $row[$name]);

                    if (strlen($row[$name]) > 50) {
                        $row[$name] = substr($row[$name], 0, 50) . '...';
                    }
                }

                $data = $row;
                if (count($data) == 0) return 0;
                break;
        }

        $user_details = AdminAuth::getDetails();

        if (!$insert_query) {
            $pdo = Pdb::getConnection('RW');
            $q = "INSERT INTO ~history_items
                (record_table, record_id, type, modified_editor, ip_address, user_agent, controller,
                    data, date_added, date_modified, parent_id)
                VALUES
                (:record_table, :record_id, :type, :modified_editor, :ip_address, :user_agent, :controller,
                    :data, :date_added, :date_modified, :parent_id)";
            $insert_query = Pdb::query($q, [], 'prep');
        }

        $insert_data = array();
        $insert_data['record_table'] = $table;
        $insert_data['record_id'] = $record_id;
        $insert_data['type'] = $type;
        $insert_data['modified_editor'] = $user_details['name'];
        $insert_data['ip_address'] = bin2hex(inet_pton(trim(Request::userIp())));
        $insert_data['user_agent'] = (string) @$_SERVER['HTTP_USER_AGENT'];
        $insert_data['controller'] = get_class($this);
        $insert_data['data'] = json_encode($data);
        $insert_data['date_added'] = Pdb::now();
        $insert_data['date_modified'] = Pdb::now();
        $insert_data['parent_id'] = $parent_log_id;

        if (!$insert_query->execute($insert_data)) return -1;

        return $pdo->lastInsertId();
    }


    /**
     * Fetches the pre-update record from the database.
     * Used by the action log system, and disabled if the action log system has been turned off
     * @param string $table Name of table to load record from
     * @param int $record_id ID of record to load
     * @return array|bool False if not using logging for this controller, else the record row
     * @throws RowMissingException If record doesn't exist
     */
    protected function loadRecord($table, $record_id)
    {
        $record_id = (int) $record_id;
        Pdb::validateIdentifier($table);
        if (!$this->action_log) return false;

        $q = "SELECT * FROM ~{$table} WHERE id = ?";
        return Pdb::query($q, [$record_id], 'row');
    }


    /**
     * Logs an add action. This is a wrapper for {@see Controller::logAction}
     * @param string $table The table which had data modified by the query
     * @param int $record_id The id of the added record
     * @return int ID of the record inserted into the history_items table
     * @throws QueryException
     */
    protected function logAdd($table, $record_id)
    {
        return $this->logAction($table, $record_id, 'Add');
    }


    /**
     * Logs the adding of a record to a category. This is a wrapper for {@see Controller::logAction}
     * @param string $table The table which had data modified by the query
     * @param int $record_id The ID of the record which was added to the category
     * @param int $cat_id The ID of the category which the record was added to
     * @return int ID of the record inserted into the history_items table
     * @throws QueryException
     */
    protected function logAddCategory($table, $record_id, $cat_id)
    {
        return $this->logAction($table, $record_id, 'Add category', ['cat_id' => $cat_id]);
    }


    /**
     * Logs an edit action. This is a wrapper for {@see Controller::logAction}
     * @param string $table The table which had data modified by the query
     * @param int $record_id The id of the edited record
     * @param array $data Pre-update data, used to build a diff
     * @return int ID of the record inserted into the history_items table
     * @throws QueryException
     */
    protected function logEdit($table, $record_id, array $data)
    {
        return $this->logAction($table, $record_id, 'Edit', $data);
    }


    /**
     * Logs a delete action. This is a wrapper for {@see Controller::logAction}
     * @param string $table The table which contained the deleted record
     * @param int $record_id The id of the deleted record
     * @param array $data The complete contents of the record which was deleted, i.e. [field => value],
     *        so it can be restored if necessary
     * @param int $parent_log_id ID of a parent log entry in the history_items table.
     *        This is used when multiple records are deleted by a single action.
     *        Undoing that action can then restore all of the deleted data.
     * @return int ID of the record inserted into the history_items table
     * @throws QueryException
     */
    protected function logDelete($table, $record_id, array $data, $parent_log_id = 0)
    {
        return $this->logAction($table, $record_id, 'Delete', $data, $parent_log_id);
    }


    /**
     * Logs the removal of a record from a category. This is a wrapper for {@see Controller::logAction}
     * @param string $table The table which contains the record which was removed from the category
     * @param int $record_id The ID of the record which was removed from the category
     * @param int $cat_id The ID of the category which the record was removed from
     * @return int ID of the record inserted into the history_items table
     * @throws QueryException
     */
    protected function logDeleteCategory($table, $record_id, $cat_id)
    {
        return $this->logAction($table, $record_id, 'Delete category', ['cat_id' => $cat_id]);
    }


    /**
     * Deletes a record, and logs the deletion
     * This should be used by all _deleteSave methods
     * Starts a transaction and commits it if not already in a transaction when called
     * @param string $table Table name
     * @param int $record_id
     * @param int $parent_log_id ID of a parent entry in the history_items table.
     *        This is used when multiple records are deleted by a single action.
     *        Undoing that action can then restore all of the deleted data.
     * @return int ID of the record inserted into the history_items table (0 if no logging)
     */
    protected function deleteRecord($table, $record_id, $parent_log_id = 0)
    {
        $record_id = (int) $record_id;

        $extant_transaction = Pdb::inTransaction();
        if (!$extant_transaction) Pdb::transact();

        if ($this->action_log) {
            /** @var PdbForeignKey[] $table_dep_cache */
            static $table_dep_cache = [];

            $record = Pdb::get($table, $record_id);

            // Look up all dependent foreign key relationships

            /** @var PdbForeignKey[][] $deps */
            $deps = [];

            /** @var string[] $base_tables */
            $base_tables = [$table];

            do {
                $new_base_tables = [];
                foreach ($base_tables as $base_table) {
                    if (array_key_exists($base_table, $table_dep_cache)) {
                        $table_deps = $table_dep_cache[$base_table];
                    } else {
                        $table_deps = Pdb::getDependentKeys($base_table);
                        $table_dep_cache[$base_table] = $table_deps;
                    }

                    /** @var PdbForeignKey[] $table_deps */

                    foreach ($table_deps as $dep) {
                        $new_base_tables[] = $dep->from_table;
                        $deps[$base_table][] = $dep;
                    }
                }
                $base_tables = array_unique($new_base_tables);
            } while (!empty($base_tables));

            // Look up all dependent data
            $data = [$table => [$record_id => $record]];
            foreach ($deps as $base_table => $table_deps) {
                $ids = @array_keys($data[$base_table]);
                if (empty($ids)) continue;

                foreach ($table_deps as $dep) {
                    if (!isset($data[$dep->from_table])) {
                        $data[$dep->from_table] = [];
                    }

                    $params = [];
                    $where = Pdb::buildClause([[$dep->from_column, 'IN', $ids]], $params);
                    $q = "SELECT * FROM ~{$dep->from_table} WHERE {$where}";
                    $res = Pdb::q($q, $params, 'pdo');
                    foreach ($res as $row) {
                        // N.B. some tables (e.g. *_cat_join) don't have an id column
                        // Such tables can't have subrecords (since the dependency relationship works from
                        // the id column), so it's fine to just use numeric array indexing on their records.
                        // The restore/undelete function should ignore the value in the record_id column in
                        // history_items, and just use what's saved in the data column.
                        if (isset($row['id'])) {
                            $data[$dep->from_table][$row['id']] = $row;
                        } else {
                            $data[$dep->from_table][] = $row;
                        }
                    }
                    $res->closeCursor();
                }
            }

            Pdb::delete($table, ['id' => $record_id]);
            $log_id = $this->logDelete($table, $record_id, $record, $parent_log_id);

            if ($parent_log_id == 0) $parent_log_id = $log_id;

            // Log deletion of per-record permissions
            if ($this instanceof ManagedAdminController) {
                $params = [];
                $conds = ['controller' => $this->getControllerName(), 'item_id' => $record_id];
                $where = Pdb::buildClause($conds, $params);

                $q = "SELECT * FROM ~per_record_permissions WHERE {$where}";
                $perms = Pdb::q($q, $params, 'arr');
                if (count($perms) > 0) {
                    $perms = Sprout::iterableFirstValue($perms);
                    Pdb::delete('per_record_permissions', ['id' => $perms['id']]);
                    $this->logDelete('per_record_permissions', $perms['id'], $perms, $parent_log_id);
                }
            }

            // Log all deleted dependent data
            foreach ($data as $data_table => $data_rows) {
                if ($data_table == $table) continue;

                foreach ($data_rows as $data_id => $data_row) {
                    $this->logDelete($data_table, $data_id, $data_row, $parent_log_id);
                }
            }

        } else {
            $log_id = 0;
            Pdb::delete($table, ['id' => $record_id]);

            if ($this instanceof ManagedAdminController) {
                $where = ['controller' => $this->getControllerName(), 'item_id' => $record_id];
                Pdb::delete('per_record_permissions', $where);
            }
        }

        if (!$extant_transaction) Pdb::commit();

        return $log_id;
    }


    /**
     * Loads a config file for a JsonForm associated with this controller
     * @param string $file_name The config file name, e.g. 'register.json'
     * @return array
     * @throws FileMissingException If the file is missing
     * @throws Exception If the file is invalid
     */
    protected function loadFormJson($file_name)
    {
        $conf_file = $this->getAbsModulePath() . '/' . $file_name;

        if (!file_exists($conf_file)) {
            throw new FileMissingException("Missing JSON file: {$conf_file}");
        } else if (filesize($conf_file) == 0) {
            throw new Exception("Empty JSON file");
        }

        $conf = file_get_contents($conf_file);
        $conf = json_decode($conf, true);
        if ($conf === null) {
            throw new Exception("Invalid JSON -- " . json_last_error_msg());
        }
        return $conf;
    }


    /**
     * Loads a JSON config file for an automated edit-type form for this controller
     * @return array
     * @throws Exception If the file is missing or invalid
     */
    protected function loadEditJson()
    {
        $full_class = get_called_class();
        $class = Sprout::removeNs($full_class);
        $class = preg_replace('/Controller$/', '', $class);
        $class = Text::camel2lc($class);

        return $this->loadFormJson("{$class}_edit.json");
    }


    /**
     * Generates a form view from a JSON config file
     * @return array
     * @throws Exception If the file is missing or invalid
     */
    protected function generateFormView($file_name)
    {
        $conf = $this->loadFormJson($file_name);
        $view = new PhpView('sprout/auto_edit');
        $view->config = $conf;
        return $view;
    }


    /**
     * Do any additional validation prior to saving the record
     *
     * @param int $id Record ID or 0 for adds
     * @param Validator $validator Validator instance to attach your errors to
     * @return void
     */
    protected function jsonExtraValidate($id, Validator $validator)
    {
        // The default implementation is empty
    }


    /**
     * Auto-set the "empty" param for fields with foreign keys to be NULL
     *
     * This greatly reduces the number of foreign-key constraints hit in day-to-day use,
     * especially with file fields.
     *
     * @param array $conf Json form configuration
     * @return null Array $conf is altered in-place
     */
    protected function autoSetEmptyParam(array &$conf)
    {
        // Find FKs on main table, set empty to null
        $fk_cols = [];
        $fks = Pdb::getForeignKeys($this->table_name);
        foreach ($fks as $row) {
            $fk_cols[] = $row->from_column;
        }

        // Iterate and do two tasks: Set empty params; collate multiedits for processing below
        $multiedits = [];
        foreach ($conf as $tab => &$items) {
            if (is_array($items)) {
                if (count($fk_cols)) {
                    JsonForm::setParameterForColumns($items, $fk_cols, 'empty', null);
                }
                foreach ($items as &$item) {
                    if (isset($item['multiedit'])) {
                        $multiedits[] = &$item['multiedit'];
                    }
                }
            }
        }
        unset($items);

        // Iterate multiedits and do the same processing
        foreach ($multiedits as &$multi) {
            $fk_cols = [];
            $fks = Pdb::getForeignKeys($multi['table']);
            foreach ($fks as $row) {
                $fk_cols[] = $row->from_column;
            }
            if (count($fk_cols)) {
                JsonForm::setParameterForColumns($multi['items'], $fk_cols, 'empty', null);
            }
        }
    }


    /**
     * Automatically saves the data associated with a submission on a JSON-generated form
     * @param array $conf Config loaded from a JSON file
     * @param int $item_id Database ID of record to store data in. If zero, a new record will be inserted, and as this
     *        argument is a reference, it will be updated with the auto-increment ID generated by the insert.
     * @param string $mode Mode: 'add', 'edit', or something custom (e.g. 'duplicate', 'verify'). If blank,
     *        'add' or 'edit' will be automatically determined, based on $item_id
     * @return bool True if the save succeeded. If false is returned, errors will be saved in $_SESSION
     */
    protected function saveJsonData(array $conf, &$item_id, $mode = '')
    {
        $item_id = (int) $item_id;

        $session_key = 'public';
        if ($this instanceof ManagedAdminController) {
            $session_key = 'admin';
        }

        $_SESSION[$session_key]['field_values'] = Validator::trim($_POST);

        if ($mode == '') $mode = ($item_id == 0 ? 'add' : 'edit');
        $validator = new Validator($_POST);
        $this->autoSetEmptyParam($conf);
        list($data, $errs) = JsonForm::collateData($conf, $mode, $validator, $item_id);

        $this->jsonExtraValidate($item_id, $validator);
        $errs = array_merge($errs, $validator->getFieldErrors());

        $_SESSION[$session_key]['field_errors'] = $errs;
        if (count($errs) > 0) {
            $validator->createNotifications();
            return false;
        }

        $this->_preSave($item_id, $data);

        $was_in_transaction = true;
        if (!Pdb::inTransaction()) {
            $was_in_transaction = false;
            Pdb::transact();
        }

        // Main insert/update, then log the action
        $base_data = [];
        foreach ($data as $key => $val) {
            if ($key == 'categories') continue;
            if (substr($key, 0, 9) == 'multiedit') continue;
            if ($key == 'uid') $val = $this->getUid($item_id);
            $base_data[$key] = $val;
        }
        if ($item_id <= 0) {
            $item_id = Pdb::insert($this->table_name, $base_data);
            $this->logAdd($this->table_name, $item_id);

            if (!empty($base_data['uid'])) {
                $data = [];
                $data['uid'] = $this->getUid($item_id);
                Pdb::update($this->table_name, $data, ['id' => $item_id]);
            }
        } else {
            $log_data = $this->loadRecord($this->table_name, $item_id);
            Pdb::update($this->table_name, $base_data, ['id' => $item_id]);
            if ($log_data) $this->logEdit($this->table_name, $item_id, $log_data);
        }

        // Update the categories
        if (isset($data['categories'])) {
            $this->updateCategories($item_id, $data['categories']);
        }

        // Update multiedits
        $id_field = Inflector::singular($this->table_name) . '_id';
        foreach ($conf as $tab_name => $tab) {
            if (!is_array($tab)) continue;
            foreach ($tab as $item) {
                if (!isset($item['multiedit'])) continue;

                $multed = $item['multiedit'];
                $table = $multed['table'];
                $multi_data_key = 'multiedit_' . $multed['id'];
                $link_column = !empty($multed['link']) ? $multed['link'] : $id_field;

                $conditions = [];
                if (isset($multed['where'])) $conditions = $multed['where'];
                $conditions[] = [$link_column, '=', $item_id];

                if (isset($data[$multi_data_key])) {
                    $defaults = [];
                    $field_defns = JsonForm::flattenGroups($multed['items']);
                    foreach ($field_defns as $field) {
                        if (array_key_exists('default', $field)) {
                            $defaults[$field['name']] = $field['default'];
                        }
                    }

                    $record_order = 0;
                    $new_set = $data[$multi_data_key];
                    foreach ($new_set as $key => &$new_rec) {
                        // Skip blank records where user hasn't entered any data
                        if (MultiEdit::recordEmpty($new_rec, $defaults)) {
                            unset($new_set[$key]);
                            continue;
                        }

                        if ($multed['reorder']) {
                            $new_rec['record_order'] = $record_order++;
                        }

                        if (!isset($new_rec[$link_column])) {
                            $new_rec[$link_column] = $item_id;
                        }
                    }
                } else {
                    $new_set = [];
                }

                $this->replaceSet($table, $new_set, $conditions);
            }
        }

        // Update autofill_lists
        foreach ($conf as $tab_name => $tab) {
            if (!is_array($tab)) continue;
            foreach ($tab as $item) {
                if (!isset($item['autofill_list'])) continue;

                $auto = $item['autofill_list'];
                $auto = JsonForm::autofillOptionDefaults($auto, $this->table_name);

                Pdb::validateIdentifier($auto['joiner_local_col']);
                Pdb::validateIdentifier($auto['joiner_foreign_col']);
                Pdb::validateIdentifier($auto['joiner_table']);

                // Post data for this field may be empty if nothing selected
                if (isset($_POST[$auto['name']])) {
                    $postdata = $_POST[$auto['name']];
                } else {
                    $postdata = [];
                }

                // It's safe to do a nuke-then-insert as there isn't IDs to keep stable
                // and this code runs within a transaction
                $conditions = [
                    $auto['joiner_local_col'] => $item_id
                ];
                Pdb::delete($auto['joiner_table'], $conditions);

                $record_order = 0;
                $foreign_ids = [];
                foreach ($postdata as $postrow) {
                    if (in_array($postrow['id'], $foreign_ids)) {
                        continue;       // ignore duplicates
                    }

                    $data = [];
                    $data[$auto['joiner_local_col']] = $item_id;
                    $data[$auto['joiner_foreign_col']] = $postrow['id'];
                    if ($auto['reorder']) {
                        $data['record_order'] = ++$record_order;
                    }
                    Pdb::insert($auto['joiner_table'], $data);
                    $foreign_ids[] = $postrow['id'];
                }
            }
        }

        if (!$was_in_transaction) Pdb::commit();

        unset($_SESSION[$session_key]['field_values']);
        unset($_SESSION[$session_key]['field_errors']);

        return true;
    }


    /**
     * Generate an appropriate UUID.
     *
     * Beware - new records are created with a UUIDv4 while the save() method
     * generates a UUIDv5. Theoretically this shouldn't be externally apparent
     * due to the wrapping transaction.
     *
     * @param int $item_id The ID of the record to generate a UUID for.
     * @return string
     * @throws Exception
     */
    private function getUid($item_id)
    {
        // Start out with a v4.
        if ($item_id == 0) return Uuid::uuid4();

        // Upgrade it later with a v5.
        $pdb = Pdb::getInstance();
        return $pdb->generateUid($this->table_name, $item_id);
    }


    /**
     * Inserts records which are in $new_records, but are not in the specified table
     * Deletes records which are in the specified table, but not in $new_records
     * Updates records which are in both
     *
     * Matching is done on the 'id' field, as a result the $new_records arrays MUST contain an id field.
     *
     * @param string $table The table name. Do not include prefix.
     * @param array $new_records The new records for the table. Should be an array of arrays,
     *        With each sub-array being the arguments which would normally be passed to Pdb::insert or Pdb::update
     * @param string $conditions A where clause to use when looking to see what records already exist. {@see Pdb::buildClause}
     * @return void
     * @throws QueryException
     */
    protected function replaceSet($table, $new_records, array $conditions)
    {
        Pdb::validateIdentifier($table);

        $values = [];
        $select_conditions = $conditions;
        $q = "SELECT id FROM ~{$table} WHERE " . Pdb::buildClause($select_conditions, $values);
        $existing = Pdb::q($q, $values, 'arr');

        // If existing record found, update, otherwise delete
        $delete_list = [];
        foreach ($existing as $old) {
            $found = null;
            foreach ($new_records as $new_idx => $new) {
                if ($old['id'] == @$new['id']) {
                    $found = $new;
                    unset($new_records[$new_idx]);
                    break;
                }
            }

            $replace_conditions = $conditions;
            $replace_conditions['id'] = $old['id'];
            if ($found) {
                unset($found['id']);
                $found['date_modified'] = Pdb::now();
                try {
                    Pdb::update($table, $found, $replace_conditions);
                } catch (QueryException $ex) {
                    unset($found['date_modified']);
                    Pdb::update($table, $found, $replace_conditions);
                }
            } else {
                $this->deleteRecord($table, $replace_conditions['id']);
            }
        }

        // Anything not updated or deleted gets added
        foreach ($new_records as $fields) {
            $fields['date_added'] = Pdb::now();
            $fields['date_modified'] = Pdb::now();
            try {
                Pdb::insert($table, $fields);
            } catch (QueryException $ex) {
                unset($fields['date_added']);
                unset($fields['date_modified']);
                Pdb::insert($table, $fields);
            }
        }
    }


} // End Controller Class
