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

use Exception;
use InvalidArgumentException;


/**
 * Processes forms using configuration stored in a JSON file which specifies database columns, their HTML input fields,
 * and validation rules.
 * A generic implementation which should work for most cases is found in {@see ManagedAdminController::_getEditForm}
 * (generates the form) and {@see Controller::saveJsonData} (saves the POST submission)
 */
class JsonForm extends Form
{

    /**
     * Loads multiedit data for use on a view
     * @param array $conf The form config, pulled from JSON
     * @param string $default_link The default linking column name
     * @param int $record_id The base record ID
     * @param array $conditions Conditions to get records in the multiedit table which relate to the base record;
     *        see {@see Pdb::buildClause}. A clause of 'link_column' => $record_id will be appended to any provided
     *        conditions.
     * @return array The View will be modified
     */
    public static function loadMultiEditData(array $conf, $default_link, $record_id, array $conditions)
    {
        $data = [];
        foreach ($conf as $tab => $tab_content) {
            if (!is_array($tab_content)) continue;

            foreach ($tab_content as $item) {
                if (!isset($item['multiedit'])) continue;

                $multed = $item['multiedit'];
                $id = 'multiedit_' . $multed['id'];
                $table = $multed['table'];

                Pdb::validateIdentifier($table);

                $values = [];
                $table_conditions = [];

                if (!empty($multed['link'])) {
                    $table_conditions[$multed['link']] = $record_id;
                } else {
                    $table_conditions[$default_link] = $record_id;
                }

                $table_conditions = array_merge($table_conditions, $conditions);
                $q = "SELECT * FROM ~{$table} WHERE " . Pdb::buildClause($table_conditions, $values);

                if ($multed['reorder']) {
                    $q .= ' ORDER BY record_order';
                }

                $data[$id] = Pdb::q($q, $values, 'arr');
            }
        }
        return $data;
    }


    /**
     * Determine the auto-generated default values for an autofill-list
     *
     * Defaults:
     *     joiner_local_col        Singular of local table name + '_id'
     *     joiner_foreign_col      Singular of the foreign_table option + '_id'
     *     foreign_label_col       'name'
     *     reorder                 false
     *
     * @param array $auto Auto link opts, from a JSON form defintion
     * @param string $local_table_name The 'local' table name (i.e. the table which is being edited)
     * @return array
     */
    public static function autofillOptionDefaults(array $auto, $local_table_name)
    {
        if (empty($auto['joiner_local_col'])) {
            $auto['joiner_local_col'] = Inflector::singular($local_table_name) . '_id';
        }
        if (empty($auto['joiner_local_col'])) {
            $auto['joiner_local_col'] = Inflector::singular($auto['foreign_table']) . '_id';
        }
        if (empty($auto['foreign_label_col'])) {
            $auto['foreign_label_col'] = 'name';
        }
        if (empty($auto['reorder'])) {
            $auto['reorder'] = false;
        }
        return $auto;
    }


    /**
     * Loads autofill_list data for use on a view
     * @param array $conf The form config, pulled from JSON
     * @param string $local_table_name The 'local' table name (i.e. the table which is being edited)
     * @param int $local_record_id The local record id
     * @param array $conditions Conditions to get records in the joiner table which relate to the base record;
     *        see {@see Pdb::buildClause}. A clause of 'link_column' => $record_id will be appended to any provided
     *        conditions.
     * @return array The View will be modified
     */
    public static function loadAutofillListData(array $conf, $local_table_name, $local_record_id, array $conditions)
    {
        $data = [];
        foreach ($conf as $tab => $tab_content) {
            if (!is_array($tab_content)) continue;

            foreach ($tab_content as $item) {
                if (!isset($item['autofill_list'])) continue;

                $auto = $item['autofill_list'];
                $auto = self::autofillOptionDefaults($auto, $local_table_name);

                // If specified use a foreign_label_sql parameter directly
                // If foreign_label_col is an array, CONCAT on space
                if (isset($auto['foreign_label_sql'])) {
                    $label_sql = $auto['foreign_label_sql'];
                } elseif (is_array($auto['foreign_label_col'])) {
                    foreach ($auto['foreign_label_col'] as $col) {
                        Pdb::validateIdentifier($col);
                    }
                    $label_sql = 'CONCAT(item.' . implode(", ' ', item.", $auto['foreign_label_col']) . ')';
                } else {
                    Pdb::validateIdentifier($auto['foreign_label_col']);
                    $label_sql = 'item.' . $auto['foreign_label_col'];
                }

                Pdb::validateIdentifier($auto['joiner_local_col']);
                Pdb::validateIdentifier($auto['joiner_foreign_col']);
                Pdb::validateIdentifier($auto['joiner_table']);
                Pdb::validateIdentifier($auto['foreign_table']);

                // Need ID for saving, value for display, and orderkey for ordering
                $fields = [];
                $fields[] = 'item.id';
                $fields[] = "{$label_sql} AS value";
                if ($auto['reorder']) {
                    $fields[] = 'joiner.record_order AS orderkey';
                }
                $fields = implode(', ', $fields);

                if ($auto['reorder']) {
                    $order = 'joiner.record_order';
                } else {
                    $order = $label_sql;
                }

                $q = "SELECT {$fields}
                    FROM ~{$auto['joiner_table']} AS joiner
                    INNER JOIN ~{$auto['foreign_table']} AS item ON item.id = joiner.{$auto['joiner_foreign_col']}
                    WHERE joiner.{$auto['joiner_local_col']} = ?
                    ORDER BY {$order}";
                $data[$auto['name']] = Pdb::query($q, [$local_record_id], 'arr');
            }
        }
        return $data;
    }


    /**
     * Expands item definitions for a field pulled from JSON
     * @param array &$field The field definition
     * @param array $metadata Metadata for use in argument replacement
     * @return void
     */
    public static function expandItemDefns(array &$field, array $metadata = [])
    {
        if (!isset($field['attrs'])) $field['attrs'] = [];
        if (!isset($field['helptext'])) $field['helptext'] = '';
        if (!isset($field['required'])) $field['required'] = false;
        if (!isset($field['items'])) {
            $field['items'] = [];
            return;
        }
        $items = &$field['items'];

        // Use a function to look up or generate items if specified
        if (isset($items['func']) and (count($items) == 1 or (count($items) == 2 and isset($items['args'])))) {
            if (strpos($items['func'], '::') !== false) {
                list($class, $func) = explode('::', $items['func']);
                $class = Sprout::nsClass($class, ['Sprout\Helpers']);
                $func = $class . '::' . $func;
            } else {
                $func = $items['func'];
            }
            $args = (isset($items['args']) ? $items['args'] : []);
            $args = self::argReplace($args, $metadata);
            $items = call_user_func_array($func, $args);

        // Run a SQL query and return a Pdb map
        } else if (isset($items['query']) and (count($items) == 1 or (count($items) == 2 and isset($items['binds'])))) {
            $binds = isset($items['binds']) ? $items['binds'] : [];
            $items = Pdb::query($items['query'], $binds, 'map');

        // Convert class vars
        } else if (isset($items['var']) and (count($items) == 1)) {
            list($class, $var) = explode('::', $items['var']);
            $class = Sprout::nsClass($class, ['Sprout\Helpers']);
            if (!class_exists($class)) {
                throw new Exception('Class lookup failed for var: ' . $items['var']);
            }
            $class_vars = get_class_vars($class);

            // Chop leading $ to convert to array reference
            $var = substr($var, 1);
            $items = $class_vars[$var];

        // Convert class constants
        } else if (isset($items['const']) and (count($items) == 1)) {
            list($class, $const) = explode('::', $items['const']);
            $class = Sprout::nsClass($class, ['Sprout\Helpers']);
            if (!class_exists($class)) {
                throw new Exception('Class lookup failed for var: ' . $items['var']);
            }
            $items = constant($class . '::' . $const);

        // Convert constants
        } else {
            foreach ($items as $key => &$item) {
                if (!is_array($item)) continue;
                if (count($item) != 1) continue;

                $item_fields = $item;
                if (isset($item_fields['const'])) {
                    if (strpos($item_fields['const'], '::') === false) continue;
                    list($class, $const) = explode('::', $item_fields['const']);
                    $class = Sprout::nsClass($class, ['Sprout\Helpers']);
                    if (defined($class. '::' . $const)) {
                        $item = constant($class. '::' . $const);
                        continue;
                    }
                    throw new Exception('Const lookup failed: ' . $item_fields['const']);
                }
            }
        }
    }


    /**
     * Render a tab item, which may be a field, heading, html block, etc
     *
     * @param array $item The item definition
     * @param string $for Either 'add', 'edit' or something custom; to check against the "for" parameter
     * @param int $id Record ID; for pass-through to function calls
     * @param int $data Data array; for pass-through to function calls
     * @param int $errors Errors array; for pass-through to function calls
     * @param string $name_prepend Prepended to the field name. Only applies for fields
     * @return html
     */
    public static function renderTabItem(array $item, $for, $id, array $data, array $errors, $name_prepend = '')
    {
        // Metadata which is passed into argReplace for display/validator argument replacement
        $metadata = [
            'id' => $id,
        ];

        if (isset($item['field'])) {
            // Field
            $field = $item['field'];

            if (!isset($field['display']) or $field['display'] == null) return null;
            if (isset($field['for'])) {
                if (!in_array($for, $field['for'])) return null;
            }

            if (!array_key_exists($field['name'], $data) and !empty($field['default'])) {
                Fb::setFieldValue($name_prepend . $field['name'], $field['default']);

                // For fields like Fb::checkboxBoolList(string $name, array $attrs, array $settings),
                // $name_prepend doesn't actually get prepended. E.g. 'active' doesn't produce 'm_active' because
                // the <input> name is set using the $settings param, not $name like most other field types
                Fb::setFieldValue($field['name'], $field['default']);
            }

            return JsonForm::renderField($field, $name_prepend, $metadata);


        } elseif (isset($item['heading'])) {
            // Heading
            return '<h3>' . Enc::html($item['heading']) . '</h3>';


        } elseif (isset($item['html'])) {
            // HTML text
            return $item['html'];


        } elseif (isset($item['group'])) {
            // Groups of similar items
            $group = $item['group'];

            if (empty($group['wrap-class'])) $group['wrap-class'] = '';
            if (empty($group['item-class'])) $group['item-class'] = '';

            $group['wrap-class'] = trim('field-group-wrap ' . $group['wrap-class']);
            $group['item-class'] = trim('field-group-item ' . $group['item-class']);

            $out = '<div class="' . $group['wrap-class'] . '">';
            foreach ($group['items'] as $group_item) {
                $out .= '<div class="' . $group['item-class'] . '">';
                $out .= self::renderTabItem($group_item, $for, $id, $data, $errors, $name_prepend);
                $out .= '</div>';
            }
            $out .= '</div>';
            return $out;


        } elseif (isset($item['func'])) {
            // Call a custom function and return the result
            if (strpos($item['func'], '::') !== false) {
                list($class, $func) = explode('::', $item['func']);
                $class = Sprout::nsClass($class, ['Sprout\Helpers']);
                $func = $class . '::' . $func;
            } else {
                $func = $item['func'];
            }

            $args = [$id, $data, $errors];
            if (isset($item['args'])) {
                $args = array_merge($args, $item['args']);
            }
            $args = self::argReplace($args, $metadata);

            return call_user_func_array($func, $args);


        } elseif (isset($item['multiedit'])) {
            // Multiedit
            $multed = $item['multiedit'];
            if (!isset($data['multiedit_' . $multed['id']])) {
                $data['multiedit_' . $multed['id']] = [];
            }
            if (!isset($errors['multiedit_' . $multed['id']])) {
                $errors['multiedit_' . $multed['id']] = [];
            }

            // Backup form data, then clobber it, to render using the multiedit's defaults
            $original_data = $data;
            $data = [];
            Fb::setData($data);

            $out = '<script type="text/x-template" id="' . Enc::html('multiedit-' . $multed['id']) . '">';
            $out .= '<input type="hidden" name="m_id">';

            foreach ($multed['items'] as $multi_item) {
                $out .= self::renderTabItem($multi_item, $for, $id, $data, $errors, 'm_');
            }

            $out .= '</script>';

            if (!empty($multed['post-add-js'])) {
                MultiEdit::setPostAddJavaScriptFunc($multed['post-add-js']);
            }
            if (!empty($multed['reorder'])) {
                MultiEdit::reorder();
            }
            MultiEdit::itemName($multed['single']);

            // Restore original form data which was clobbered to render defaults
            Fb::setData($original_data);
            $data = $original_data;

            ob_start();
            MultiEdit::display(
                $multed['id'],
                $data['multiedit_' . $multed['id']],
                $errors['multiedit_' . $multed['id']]
            );
            $out .= ob_get_clean();

            return $out;

        } elseif (isset($item['autofill_list'])) {
            // The autofillList method receives the whole object in $options straight from the JSON
            $auto = $item['autofill_list'];
            return Form::autofillList($auto['name'], [], $auto);

        } else {
            throw new InvalidArgumentException(
                "Unknown item type; expected key 'field', 'heading', 'html', 'func', 'multiedit', or 'autofill_list'"
            );
        }
    }


    /**
     * Renders the input for a field definition pulled from a JSON file
     * @param array $field The field definition
     * @param string $name_prepend Prepended to the field name
     * @param array $metadata Metadata for use in argument replacement
     * @return string
     */
    public static function renderField(array $field, $name_prepend = '', $metadata = [])
    {
        self::expandItemDefns($field, $metadata);

        $func = $field['display'];
        if (strpos($func, '::') !== false) {
            list($class, $func) = explode('::', $func);
            $class = Sprout::nsClass($class, ['Sprout\Helpers']);
            $func = $class . '::' . $func;
        }

        if (!is_callable($func)) {
            throw new InvalidArgumentException("Field display method '{$func}' does not exist");
        }

        Form::nextFieldDetails($field['label'], $field['required'], $field['helptext']);
        return Form::fieldAuto($func, $name_prepend . $field['name'], $field['attrs'], $field['items']);
    }


    /**
     * Set a parameter for fields to be a specific value, for one or more columns
     *
     * @param array $items Items array, may contain fields, groups, etc
     * @param array $columns Columns to alter, as an array of strings (e.g. ['file','image'])
     * @param string $key Key to set
     * @param string $val Value to set the key to
     * @return null Array $items is altered in-place
     */
    public static function setParameterForColumns(array &$items, array $columns, $key, $val)
    {
        foreach ($items as &$item) {
            if (isset($item['field']) and in_array($item['field']['name'], $columns)) {
                $item['field'][$key] = $val;
            } else if (isset($item['group'])) {
                self::setParameterForColumns($item['group']['items'], $columns, $key, $val);
            }
        }
    }


    /**
     * Extract field defns from a list (which may include groups)
     *
     * @param array $items Item defintions, e.g. from a tab
     * @return array Field defintions only, in a flat list
     **/
    public static function flattenGroups(array $items)
    {
        $field_defns = [];

        foreach ($items as $item) {
            if (isset($item['field'])) {
                $field_defns[] = $item['field'];
            } else if (isset($item['group'])) {
                $field_defns = array_merge(
                    $field_defns,
                    self::flattenGroups($item['group']['items'])
                );
            }
        }

        return $field_defns;
    }


    /**
     * Collates POST data using specified config options
     * @param array $conf Config, typically loaded by Controller::loadFormJson()
     * @param string $mode Form mode, e.g. 'add', 'edit' or a custom value
     * @param Validator $validator To validate the data; must be created externally so it can be used for other
     *        validation before and/or after collating the JsonForm data
     * @param int $item_id The record being edited or 0 for record adding
     * @return array [0] Data for insert/update, field => value [1] Errors generated, field => error
     */
    public static function collateData($conf, $mode, Validator $validator, int $item_id)
    {
        $data = [];
        $errs = [];
        foreach ($conf as $tab => $tab_content) {
            if ($tab_content === 'categories') {
                $data['categories'] = [];
                if (@is_array($_POST['categories'])) {
                    foreach ($_POST['categories'] as $cat_id) {
                        $cat_id = (int) $cat_id;
                        if ($cat_id > 0) $data['categories'][] = $cat_id;
                    }
                }
                continue;
            }
            if (!is_array($tab_content)) continue;

            // Metadata which is passed into argReplace for display/validator argument replacement
            $metadata = [
                'id' => $item_id,
            ];

            // Main fields
            $field_defns = self::flattenGroups($tab_content);
            foreach ($field_defns as $field_defn) {
                if (isset($field_defn['for']) and !in_array($mode, $field_defn['for'])) continue;
                $validator->setFieldLabel($field_defn['name'], @$field_defn['label']);
                if (strpos($field_defn['name'], ',') === false) {
                    self::collateFieldData($field_defn, @$_POST[$field_defn['name']], $metadata, $validator, $data);
                } else {
                    $errors = [];
                    foreach (explode(',', $field_defn['name']) as $name) {
                        // Prevent errors from going into main validation until they have been grouped
                        $segment_validator = new Validator($_POST);

                        $temp_defn = $field_defn;
                        $temp_defn['name'] = $name;
                        self::collateFieldData($temp_defn, @$_POST[$name], $metadata, $segment_validator, $data);
                        $field_errors = $segment_validator->getFieldErrors();
                        if (isset($field_errors[$name])) {
                            $errors = array_merge($errors, $field_errors[$name]);
                        }
                    }
                    foreach ($errors as $err) {
                        $validator->addFieldError($field_defn['name'], $err);
                    }
                }
            }
            $errs = array_merge($errs, $validator->getFieldErrors());

            // Multiedits
            $valid = [];
            foreach ($tab_content as $item) {
                if (!isset($item['multiedit'])) continue;

                $multed = $item['multiedit'];
                $src = 'multiedit_' . $multed['id'];

                // User has removed all multiedit records of this type
                if (!isset($_POST[$src])) continue;

                $data[$src] = [];
                $valid[$src] = [];
                $defaults = [];
                $field_defns = self::flattenGroups($multed['items']);
                foreach ($field_defns as $field_defn) {
                    $field = $field_defn['name'];

                    if (array_key_exists('default', $field_defn)) {
                        $defaults[$field] = $field_defn['default'];
                    }

                    foreach ($_POST[$src] as $item_num => $val) {
                        if (!isset($val[$field])) $val[$field] = '';
                        if (!isset($data[$src][$item_num])) $data[$src][$item_num] = [];
                        if (!isset($errs[$src][$item_num])) $errs[$src][$item_num] = [];
                        if (!isset($valid[$src][$item_num])) $valid[$src][$item_num] = new Validator([]);

                        if (!isset($data[$src][$item_num]['id']) and isset($val['id'])) {
                            $data[$src][$item_num]['id'] = (int) $val['id'];
                        }

                        $valid[$src][$item_num]->setFieldValue($field_defn['name'], $val[$field]);
                        self::collateFieldData(
                            $field_defn,
                            $val[$field],
                            $metadata,
                            $valid[$src][$item_num],
                            $data[$src][$item_num]
                        );
                    }
                }

                $errs[$src] = [];
                foreach ($valid[$src] as $item_num => $v) {
                    if ($v->hasErrors()) {
                        $errs[$src][$item_num] = $v->getFieldErrors();
                    }
                }

                // Prune empty records, so user doesn't get an error about their required fields
                foreach ($data[$src] as $item_num => $record) {
                    if (MultiEdit::recordEmpty($record, $defaults)) {
                        unset($data[$src][$item_num]);
                        unset($errs[$src][$item_num]);
                    }
                }
                if (count($errs[$src]) == 0) unset($errs[$src]);
            }
        }
        return [$data, $errs];
    }


    /**
     * Collates a single field's $_POST data for INSERT/UPDATE queries, and performs validation
     *
     * @param array $field_defn Field definition from JSON file
     * @param string $input The POSTed input for the field, usually just from $_POST[field_name]
     * @param Validator $valid The validator instance to do validation with
     * @param array &$data Data for DB insert/update
     */
    protected static function collateFieldData(array $field_defn, $input, array $metadata, Validator $valid, array &$data)
    {
        // Don't save anything for display-only fields
        if (isset($field_defn['save']) and !$field_defn['save']) return;

        $field = $field_defn['name'];

        if (is_array($input)) {
            $data[$field] = implode(',', $input);
        } else {
            $data[$field] = $input;
        }

        if (Validator::isEmpty($input)) {
            if (array_key_exists('empty', $field_defn)) {
                $data[$field] = $field_defn['empty'];
            } else {
                $data[$field] = '';
            }
            $valid->setFieldValue($field, $data[$field]);
        }

        if (!empty($field_defn['required'])) {
            $valid->required([$field]);
        }

        if (isset($field_defn['validate'])) {
            foreach ($field_defn['validate'] as $call) {
                if (!isset($call['func'])) continue;

                $call['args'] = self::argReplace($call['args'], $metadata);

                switch (@count($call['args'])) {
                    case 0:
                        $valid->check($field, $call['func']);
                        break;
                    case 1:
                        $valid->check($field, $call['func'], $call['args'][0]);
                        break;
                    case 2:
                        $valid->check($field, $call['func'], $call['args'][0], $call['args'][1]);
                        break;
                    case 3:
                        $valid->check($field, $call['func'], $call['args'][0], $call['args'][1], $call['args'][2]);
                        break;
                    default:
                        $args = array_merge([$field, $call['func']], $call['args']);
                        call_user_func_array(array($valid, 'check'), $args);
                        break;
                }
            }
        }
    }


    /**
     * Replace magic strings in "args" arrays with various metadata values
     *
     * Replacements:
     *     %%       The current record id
     *
     * @param array $args Arguments in the JsonForm definition
     * @param array $metadata Metadata array
     * @return array Mogrified arguments
     */
    private static function argReplace(array $args, array $metadata)
    {
        foreach ($args as &$arg) {
            if ($arg === '%%') {
                $arg = $metadata['id'];
            }
        }

        return $args;
    }


    /**
     * Modify a JSON form config to make a particular field optional
     * @param array $conf The JSON form config
     * @param string $field_name The name of the field
     */
    public static function makeOptional(array &$conf, $field_name)
    {
        self::changeFieldRequired($conf, $field_name, false);
    }


    /**
     * Modify a JSON form config to make a particular field required
     * @param array $conf The JSON form config
     * @param string $field_name The name of the field
     */
    public static function makeRequired(array &$conf, $field_name)
    {
        self::changeFieldRequired($conf, $field_name, true);
    }


    /**
     * Modify a JSON form config to change the 'required' status of a particular field
     * This implements {@see JsonForm::makeOptional} and {@see JsonForm::makeRequired}
     * @param array $conf The JSON form config
     * @param string $field_name The name of the field
     * @param bool $required True for required, false for optional
     */
    protected static function changeFieldRequired(array &$conf, $field_name, $required)
    {
        $required = (bool) $required;

        foreach ($conf as $tab_name => &$tab) {
            // Ignore e.g. categories
            if (!is_array($tab)) continue;

            foreach ($tab as &$item) {
                if (isset($item['field'])) {
                    if ($item['field']['name'] != $field_name) continue;
                    $item['field']['required'] = $required;
                    return;
                } else if (isset($item['group'])) {
                    if (!@is_array($item['group']['items'])) continue;
                    foreach ($item['group']['items'] as &$group_item) {
                        if (!isset($group_item['field'])) continue;
                        if ($group_item['field']['name'] != $field_name) continue;
                        $group_item['field']['required'] = $required;
                        return;
                    }
                }
            }
        }
    }

}
