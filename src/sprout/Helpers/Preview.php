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

use InvalidArgumentException;

use Sprout\Controllers\Admin\ManagedAdminController;
use Sprout\Controllers\Controller;


/**
 * Somewhat complicated preview system which copies existing data into
 * temporary tables, then renders the preview via a front-end method call
 */
class Preview {

    /**
     * Loads a preview by creating temporary tables in SQL, calling a
     * controller's _editSave method, and then calling a method it would
     * normally use to display its (non-temporary) data
     *
     * @param ManagedAdminController $ctlr The controller with the preview method
     * @param array $tables Tables to copy. The keys are the table names, and
     *        the values are WHERE clauses for data to copy across: use 0 to
     *        copy just the structure with no data, and 1 to copy all data;
     *        otherwise use an array of conditions (see {@see Pdb::buildClause})
     *        The first table specified is the 'core' table which will contain
     *        the record which will be previewed.
     *        N.B. It's unnecessary to specify pages, page_revisions, or
     *        menu_groups here; they will be included automatically.
     * @param int $record_id ID of the main record, if it exists; 0 to insert a new record
     * @return int The ID of the record (whether existing or newly inserted)
     */
    public static function load(ManagedAdminController $ctlr, array $tables, $record_id = 0)
    {
        // Tables that are always required for the main nav to work
        $subsite = (int) $_SESSION['admin']['active_subsite'];
        $defaults = array(
            'pages' => 'parent_id = 0 AND subsite_id = ' . $subsite,
            'page_revisions' => "status = 'live'",
            'menu_groups' => 'subsite_id = ' . $subsite,
        );
        foreach ($defaults as $table => $where) {
            if (!isset($tables[$table])) $tables[$table] = $where;
        }

        // Generate new prefix for temporary tables
        $rand = Sprout::randStr(8);
        $old_pf = Pdb::prefix();
        $new_pf = "preview_{$rand}_";

        foreach ($tables as $table => $conditions) {
            Pdb::validateIdentifier($table);
            if ($conditions == 0) {
                $conditions = ['0=1'];
            } else if ($conditions == 1) {
                $conditions = ['1=1'];
            } else if (!is_array($conditions)) {
                throw new InvalidArgumentException('Conditions must be 1, 0, or an array');
            }

            $new = "{$new_pf}{$table}";
            $old = "{$old_pf}{$table}";

            $params = [];
            $where = Pdb::buildClause($conditions, $params);

            // Ideally this should be CREATE TEMPORARY TABLE ... LIKE ...
            // But it's unuseable due to a MySQL (5.1?) bug - see e.g.
            // https://bugs.mysql.com/bug.php?id=60574
            $q = "CREATE TEMPORARY TABLE {$new} SELECT * FROM {$old} WHERE {$where}";
            Pdb::q($q, $params, 'null');

            Pdb::setTablePrefixOverride($table, $new_pf);
        }

        $table_names = array_keys($tables);
        $core_table = reset($table_names);

        // The CREATE TEMPORARY TABLE ... SELECT above means there's no AUTO_INCREMENT on the id column,
        // so add one before inserting a new record
        if ($record_id <= 0) {
            $q = "ALTER TABLE ~{$core_table} MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
            Pdb::q($q, [], 'null');
            $record_id = Pdb::insert($core_table, ['id' => 0]);
        }

        $ctlr->_editSave($record_id);

        // Edit save has likely set these during validation
        // Remove them to avoid weird behaviour
        unset($_SESSION['admin']['field_values']);
        unset($_SESSION['admin']['field_errors']);

        return $record_id;
    }


    /**
     * Run a controller method using the preview data.
     * This injects the word 'PREVIEW' into the page title
     *
     * @param Controller $ctlr
     * @param string $method The method to run
     * @param array $args Arguments for the method
     * @return void This calls echo
     */
    public static function run(Controller $ctlr, $method, array $args = [])
    {
        ob_start();
        call_user_func_array([$ctlr, $method], $args);
        $content = ob_get_clean();

        echo preg_replace(
            '/<title[^>]*?>(.*?)<\/title>/i',
            '<title>PREVIEW - $1</title>',
            $content
        );
    }

}
