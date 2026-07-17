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

use Exception;
use karmabunny\kb\Uuid;
use Nette\Neon\Neon;
use Sprout\Controllers\Admin\HasCategoriesAdminController;
use Sprout\Exceptions\FileMissingException;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\JsonForm;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Text;
use Sprout\Helpers\Validator;

/**
 * A controller that implements JSON forms.
 */
abstract class JsonFormsController extends RecordController
{

    /**
     * The key to use for session storage of form data
     *
     * @var string
     */
    protected $form_session_key = 'public';


    /**
     * The name of the table for this controller.
     *
     * @return string
     */
    public abstract function getTableName(): string;



    /**
     * Generate an appropriate UUID.
     *
     * Beware - new records are created with a UUIDv4 while the save() method
     * generates a UUIDv5. Theoretically this shouldn't be externally apparent
     * due to the wrapping transaction.
     *
     * @param int $item_id The ID of the record to generate a UUID for.
     * @return string
     */
    protected function getUid($item_id)
    {
        // Start out with a v4.
        if ($item_id == 0) return Uuid::uuid4();

        // Upgrade it later with a v5.
        $pdb = Pdb::getInstance();
        $table_name = $this->getTableName();
        return $pdb->generateUid($table_name, $item_id);
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
        $neon_file = $this->getAbsModulePath() . "/{$file_name}.neon";
        $json_file = $this->getAbsModulePath() . "/{$file_name}.json";

        if (file_exists($neon_file)) {
            $conf = file_get_contents($neon_file);

            if (empty($conf)) {
                throw new Exception("Empty NEON file");
            }

            $conf = Neon::decode($conf);

            if ($conf === null) {
                throw new Exception("Invalid NEON file");
            }

            return $conf;
        }

        if (file_exists($json_file)) {
            $conf = file_get_contents($json_file);

            if (empty($conf)) {
                throw new Exception("Empty JSON file");
            }

            $conf = json_decode($conf, true);

            if ($conf === null) {
                throw new Exception("Invalid JSON -- " . json_last_error_msg());
            }

            return $conf;
        }

        $conf_name = $this->getModulePath() . "/{$file_name}";
        throw new FileMissingException("Missing JSON file: {$conf_name}");
    }


    /**
     * Loads a JSON config file for an automated edit-type form for this controller
     * @return array<string, array<array<string, mixed>>>
     * @throws Exception If the file is missing or invalid
     */
    protected function loadEditJson()
    {
        $full_class = get_called_class();
        $class = Sprout::removeNs($full_class);
        $class = preg_replace('/Controller$/', '', $class);
        $class = Text::camel2lc($class);

        return $this->loadFormJson("{$class}_edit");
    }


    /**
     * Generates a form view from a JSON config file
     * @return PhpView
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
     * @param array<string, array<array<string, mixed>>> $conf Json form configuration
     * @return void Array $conf is altered in-place
     */
    protected function autoSetEmptyParam(array &$conf)
    {
        $table_name = $this->getTableName();

        // Find FKs on main table, set empty to null
        $fk_cols = [];
        $fks = Pdb::getForeignKeys($table_name);
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
     * @param array<string, array<array<string, mixed>>> $conf Config loaded from a JSON file
     * @param int $item_id Database ID of record to store data in. If zero, a new record will be inserted, and as this
     *        argument is a reference, it will be updated with the auto-increment ID generated by the insert.
     * @param string $mode Mode: 'add', 'edit', or something custom (e.g. 'duplicate', 'verify'). If blank,
     *        'add' or 'edit' will be automatically determined, based on $item_id
     * @return bool True if the save succeeded. If false is returned, errors will be saved in $_SESSION
     */
    protected function saveJsonData(array $conf, &$item_id, $mode = '')
    {
        $item_id = (int) $item_id;

        $table_name = $this->getTableName();

        $_SESSION[$this->form_session_key]['field_values'] = Validator::trim($_POST);

        if ($mode == '') $mode = ($item_id == 0 ? 'add' : 'edit');
        $validator = new Validator($_POST);
        $this->autoSetEmptyParam($conf);
        list($data, $errs) = JsonForm::collateData($conf, $mode, $validator, $item_id);

        $this->jsonExtraValidate($item_id, $validator);
        $errs = array_merge($errs, $validator->getFieldErrors());

        $_SESSION[$this->form_session_key]['field_errors'] = $errs;
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
            $item_id = Pdb::insert($table_name, $base_data);
            $this->logAdd($table_name, $item_id);

            if (!empty($base_data['uid'])) {
                $data = [];
                $data['uid'] = $this->getUid($item_id);
                Pdb::update($table_name, $data, ['id' => $item_id]);
            }
        } else {
            $log_data = $this->loadRecord($table_name, $item_id);
            Pdb::update($table_name, $base_data, ['id' => $item_id]);
            if ($log_data) $this->logEdit($table_name, $item_id, $log_data);
        }

        // Update the categories
        if (isset($data['categories']) and $this instanceof HasCategoriesAdminController) {
            $this->updateCategories($item_id, $data['categories']);
        }

        // Update multiedits
        $id_field = Inflector::singular($table_name) . '_id';
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
                $auto = JsonForm::autofillOptionDefaults($auto, $table_name);

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

        unset($_SESSION[$this->form_session_key]['field_values']);
        unset($_SESSION[$this->form_session_key]['field_errors']);

        return true;
    }



}
