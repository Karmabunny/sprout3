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
 */
namespace Sprout\Controllers;

use InvalidArgumentException;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use karmabunny\pdb\Models\PdbForeignKey;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Request;
use Sprout\Helpers\Sprout;

/**
 * A base class for controllers that handle records.
 *
 * Largely this just provides action log helpers.
 */
abstract class RecordController extends Controller
{


    /**
     * Should this controller log add/edit/delete actions?
     *
     * @var bool
     */
    protected $action_log = false;


    /**
     * The short name of the controller.
     *
     * Within the `RecordController`, if this is empty then no per-record permissions will be logged.
     *
     * @return string
     */
    public function getControllerName(): string
    {
        return '';
    }

    /**
     * @param int $id
     * @param array<string, mixed> $data
     * @return void
     */
    protected function _preSave($id, &$data)
    {
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
     * @throws QueryException
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
                $ids = @array_keys($data[$base_table] ?? []);
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
            if ($controller = $this->getControllerName()) {
                $params = [];
                $conds = ['controller' => $controller, 'item_id' => $record_id];
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

            if ($controller = $this->getControllerName()) {
                $where = ['controller' => $controller, 'item_id' => $record_id];
                Pdb::delete('per_record_permissions', $where);
            }
        }

        if (!$extant_transaction) Pdb::commit();

        return $log_id;
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
     * @param array $conditions A where clause to use when looking to see what records already exist. {@see Pdb::buildClause}
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


    /**
     * Stores a history item in the database, recording an add (i.e. insert), edit, or delete.
     *
     * Should be called AFTER the action has been made.
     *
     * This is a low-level method; the friendlier wrapper methods are preferred;
     * e.g. {@see logAdd}, {@see logEdit}
     *
     * @param string $table The table which had data modified by the query
     * @param int $record_id The id of the added/edited/deleted record
     * @param string $type ENUM value from history_items.type
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
    protected function logAction(string $table, int $record_id, string $type, array $data = [], int $parent_log_id = 0): int
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

                    $row[$name] = trim(strip_tags($row[$name] ?? ''));
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
     *
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
     * Gets all dependent records for a given record.
     *
     * @param string $table
     * @param int $record_id
     * @param array $record The record to be deleted
     * @return array
     */
    protected function getDependentRecords(string $table, int $record_id, array $record): array
    {
        /** @var PdbForeignKey[][] $table_dep_cache */
        static $table_dep_cache = [];

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
            $ids = @array_keys($data[$base_table] ?? []);
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

        unset($data[$table]);
        return $data;
    }


    /**
     * Logs an add action.
     *
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
     * Logs the adding of a record to a category.
     *
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
     * Logs an edit action.
     *
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
     * Logs a delete action.
     *
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
     * Logs the removal of a record from a category.
     *
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
}
