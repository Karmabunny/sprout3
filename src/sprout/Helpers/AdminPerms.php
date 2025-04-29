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

use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;


/**
* Provides user permisison functions for the admin
**/
class AdminPerms
{
    public static $access_flags;
    public static $subsites_permitted;

    /**
    * Checks whether the currently logged in operator can access the specified item.
    * This method should be used for tree-based tables, like the 'pages' table,
    * which may inherit their permissions from the parent record.
    *
    * @param string $table The table name of the item to check
    * @param int $id The id of the record to check
    * @returns boolean True if the operator has access, false otherwise
    **/
    public static function checkPermissionsTree($table, $id)
    {
        Session::instance();

        // Not logged in - nothing
        if (! $_SESSION['admin']['login_id']) return false;

        // Super users can access everything
        if ($_SESSION['admin']['super']) return true;

        // Standard users - find out which groups can access this page
        $access_groups = self::getAccessableGroups($table, $id);
        if (count($access_groups) == 0) return false;

        // Check if the user is in any of those groups
        $params = [];
        $conditions = ['operator_id' => $_SESSION['admin']['login_id'], ['cat_id', 'IN', $access_groups]];
        $q = "SELECT 1
            FROM ~operators_cat_join
            WHERE " . Pdb::buildClause($conditions, $params);
        try {
            Pdb::query($q, $params, 'row');
            return true;
        } catch (QueryException $ex) {
            return false;
        }
    }


    /**
    * Returns a list of groups (operator categories) which can access a specific page.
    *
    * Each page can either specify permissions, or inherit permissions from it's parent page.
    *
    * @example
    *     $cat_ids = AdminPerms::getAccessableGroups('pages', 10);
    *
    * @param string $table The table to get permissions for
    * @param int $id The ID of the record to get permissions for
    * @return array Integers, one per category of operator which has access
    * @return false No operators have access
    **/
    public static function getAccessableGroups($table, $id)
    {
        // The top level node always allows all categories
        if ($id == 0) {
            return array_keys(AdminAuth::getAllCategories());
        }

        // Fetch the permissions for this page, along with some page details
        $single = Inflector::singular($table);
        $q = "SELECT tbl.parent_id, tbl.admin_perm_type, perms.category_id
            FROM ~{$table} AS tbl
            LEFT JOIN ~{$single}_admin_permissions AS perms ON perms.item_id = tbl.id
            WHERE tbl.id = ?";
        $res = Pdb::q($q, [$id], 'arr');

        // No records found, so no permissions
        if (count($res) == 0) {
            return false;
        }

        switch ($res[0]['admin_perm_type']) {
            case Constants::PERM_INHERIT:
                // Inherit from parent record
                return self::getAccessableGroups($table, $res[0]['parent_id']);
                break;

            case Constants::PERM_SPECIFIC:
                // Grab the category IDs from the resultset fetched earlier
                $items = [];
                foreach ($res as $row) {
                    if ($row['category_id']) {
                        $items[] = $row['category_id'];
                    }
                }
                return $items;
                break;
        }
    }


    /**
    * Gets all 'access' flags for this user.
    * Access flags are specified per operator-group.
    * This function will return the values for all of the defined access flags,
    *    returning the greatest-permission flag available for the user.
    * Will get data for the currently logged in user
    **/
    public static function loadAccessFlags()
    {
        Session::instance();

        self::$access_flags = array(
            'access_operators' => 0,
            'access_noapproval' => 0,
            'access_reportemail' => 0,
            'access_homepage' => 0,
        );

        // Not logged in - nothing
        if (empty($_SESSION['admin']['login_id'])) return;

        // Super users can access everything
        if ($_SESSION['admin']['super']) {
            foreach (self::$access_flags as $key => $val) {
                self::$access_flags[$key] = 1;
            }
            return;
        }


        // Get the values of the operator flags
        $flag_names = implode(', ', array_keys(self::$access_flags));
        $q = "SELECT {$flag_names}
            FROM ~operators_cat_list AS cat
            INNER JOIN ~operators_cat_join AS joiner ON cat.id = joiner.cat_id
            WHERE joiner.operator_id = ?";
        $res = Pdb::q($q, [$_SESSION['admin']['login_id']], 'pdo');

        // Grab the highest value for each flag
        foreach ($res as $op) {
            foreach (self::$access_flags as $name => $value) {
                self::$access_flags[$name] = max($op[$name], $value);
            }
        }
        $res->closeCursor();
    }


    /**
    * Returns true or false depending on if an access flag is available for this user or not
    **/
    public static function canAccess($access_flag)
    {
        self::loadAccessFlags();
        return (boolean) self::$access_flags[$access_flag];
    }


    /**
    * Gets all permitted subsite IDs for this user.
    * Subsite permissions are specified per operator-group.
    * This function will return an array of subsite IDs.
    * Will get data for the currently logged in user
    *
    * @param array $operator_cats An array of categories the operator is permitted to work with
    **/
    public static function loadSubsitesPermitted()
    {
        Session::instance();

        // Not logged in - return all false
        if (empty($_SESSION['admin']['login_id'])) {
            return [];
        }

        try {
            $subsites = Pdb::lookup('subsites');
        } catch (QueryException $ex) {
            // Assume DB has no tables
            $subsites = [];
        }

        self::$subsites_permitted = array();

        // Super users can access everything
        if (isset($_SESSION['admin']['super'])) {
            // Pretend there's a subsite if DB has no tables
            if (count($subsites) == 0) $subsites = [1 => 'Site with no DB'];

            foreach ($subsites as $key => $val) {
                self::$subsites_permitted[$key] = true;
            }
            return self::$subsites_permitted;
        }


        $cats = array(0);
        $cats = array_merge($cats, AdminAuth::getOperatorCategories());

        // If set to default (ie all) on any category then grant all subsites
        $params = [];
        $where = Pdb::buildClause([['id', 'IN', $cats]], $params);
        $admin_id = AdminAuth::getId();
        $q = "SELECT access_all_subsites
            FROM ~operators_cat_list
            WHERE {$where}";
        $res = Pdb::q($q, $params, 'col');

        foreach ($res as $access_all_subsites) {
            if ($access_all_subsites) {
                foreach ($subsites as $key => $val) {
                    self::$subsites_permitted[$key] = true;
                }
                return self::$subsites_permitted;
            }
        }

        // Get the values of the subsite settings
        $params = [];
        $where = Pdb::buildClause([['operatorcategory_id', 'IN', $cats]], $params);
        $q = "SELECT subsite_id
            FROM ~operatorcategory_subsites
            WHERE {$where}";
        $subs_ops = Pdb::q($q, $params, 'col');

        // Grab the current settings and load into array
        foreach ($subs_ops as $subsite_id) {
            self::$subsites_permitted[$subsite_id] = true;
        }

        return self::$subsites_permitted;
    }


    /**
    * Returns true or false depending on if a subsite is available for this user or not
    **/
    public static function canAccessSubsite($subsite_id)
    {
        self::loadSubsitesPermitted();
        return self::$subsites_permitted[$subsite_id] ?? false;
    }


    /**
     * Get a list of all operators with a specific access flag
     * @param string $access_flag One of the fields in the operators_cat_list table, e.g. 'access_homepage'
     * @return array Matching rows from the operators table
     * @throws QueryException
     */
    public static function getOperatorsWithAccess($access_flag)
    {
        Pdb::validateIdentifier($access_flag);

        $q = "SELECT DISTINCT op.*
            FROM ~operators AS op
            INNER JOIN ~operators_cat_join AS joiner ON joiner.operator_id = op.id
            INNER JOIN ~operators_cat_list AS cat ON joiner.cat_id = cat.id
            WHERE cat.{$access_flag} = 1
            GROUP BY op.id
            ORDER BY op.id";
        return Pdb::q($q, [], 'arr');
    }


    /**
    * Returns true or false depending on if an access flag is available for this user or not
    *
    * @param string $controller The name of the controller to check an access flag for
    * @param string $access_flag The flag to check (e.g. 'main', 'add', 'edit', etc)
    **/
    public static function controllerAccess($controller, $access_flag)
    {
        Session::instance();

        if (! $_SESSION['admin']['login_id']) return false;
        if ($_SESSION['admin']['super']) return true;


        // Home page - use the dedicated checkbox instead.
        if ($controller == 'home_page') {
            return AdminPerms::canAccess('access_homepage');
        }

        // Operators have special logic at the controller level for edits
        // to allow operators to change their own passwords
        if ($controller == 'operator') return true;

        // These tools are controlled by the 'operators' flag as well
        if ($controller == 'tools' or $controller == 'action_log' or $controller == 'subsites') {
            return AdminPerms::canAccess('access_operators');
        }



        // Grab a list of categories this user is in
        $q = "SELECT cats.id, cats.default_allow
            FROM ~operators_cat_list AS cats
            INNER JOIN ~operators_cat_join AS joiner ON cats.id = joiner.cat_id
            WHERE joiner.operator_id = ?";
        $res = Pdb::q($q, [AdminAuth::getId()], 'arr');

        // No categories? That's an error.
        if (count($res) == 0) {
            throw new Exception('The currently logged-in operator isn\'t in any categories');
        }

        // Grab the ids, and also find the highest value for the default field
        $cat_ids = array();
        $default_allow = 0;
        foreach ($res as $row) {
            $cat_ids[] = $row['id'];
            $default_allow = max($default_allow, $row['default_allow']);
        }

        // Prep for the query
        $access_flag = trim(strtolower($access_flag));

        // The main query just finds the highest value access flag and returns it
        // If there isn't anything, use the default
        $params = [];
        $conditions = [['operatorcategory_id', 'IN', $cat_ids], 'controller' => $controller];
        $where = Pdb::buildClause($conditions, $params);
        $q = "SELECT MAX(access_{$access_flag}) AS flag
            FROM ~operatorcategory_permissions
            WHERE {$where}
            LIMIT 1";
        try {
            $flag = Pdb::q($q, $params, 'val');
            if ($flag === null) return ($default_allow == 1);
            return $flag;
        } catch (RowMissingException $ex) {
            return ($default_allow == 1);
        }
    }


    /**
    * For a given list of controllers, return a list of controllers which the current user can actually access.
    * This controls which tabs show in the navigation.
    * It checks for the 'contents' access flag.
    **/
    public static function controllerAccessMulti(array $controllers)
    {
        Session::instance();

        if (! $_SESSION['admin']['login_id']) return array();
        if ($_SESSION['admin']['super']) return $controllers;


        // Grab a list of categories this user is in
        $q = "SELECT cats.id, cats.default_allow
            FROM ~operators_cat_list AS cats
            INNER JOIN ~operators_cat_join AS joiner ON joiner.cat_id = cats.id
            WHERE joiner.operator_id = ?";
        $res = Pdb::q($q, [AdminAuth::getId()], 'arr');

        // No categories? That's an error.
        if (count($res) == 0) {
            throw new Exception('The currently logged-in operator isn\'t in any categories');
        }

        // Grab the ids, and also find the highest value for the default field
        $cat_ids = array();
        $default_allow = 0;
        foreach ($res as $row) {
            $cat_ids[] = $row['id'];
            $default_allow = max($default_allow, $row['default_allow']);
        }

        // The main query just finds the highest value access flag and returns it
        $params = [];
        $conditions = [['operatorcategory_id', 'IN', $cat_ids], ['controller', 'IN', $controllers]];
        $where = Pdb::buildClause($conditions, $params);
        $q = "SELECT controller, MAX(access_contents) AS flag
            FROM ~operatorcategory_permissions
            WHERE {$where}
            GROUP BY controller";
        $res = Pdb::q($q, $params, 'arr');

        // Build the response array
        $out = array();
        foreach ($res as $row) {
            if ($row['flag']) $out[] = $row['controller'];
            unset($controllers[array_search($row['controller'], $controllers)]);
        }

        // If the default is "allow", add any controllers which didn't return a result
        if ($default_allow == 1) {
            foreach ($controllers as $c) {
                $out[] = $c;
            }
        }

        // These are managed elsewhere, so we always allow access here
        $out[] = 'home_page';
        $out[] = 'operator';
        $out[] = 'tools';
        $out[] = 'action_log';
        $out[] = 'subsites';

        return $out;
    }


    /**
    * Get the name of the first controller the user has access to.
    * For most users, this will be 'pages', but it might be something else.
    **/
    public static function getFirstAccessable()
    {
        $controller_names = array_keys(Register::getAdminControllers());
        array_unshift($controller_names, 'user');
        array_unshift($controller_names, 'file');
        array_unshift($controller_names, 'page');

        $allowed_ctlrs = AdminPerms::controllerAccessMulti($controller_names);

        return $allowed_ctlrs[0];
    }


    /**
     * Remove controllers and tiles for which the user doesn't have access
     *
     * @param array Tile definitions, loaded from {@see Register::getAdminTiles}
     * @return array Tile definitions sans unpermitted controllers
     */
    public static function filterAdminTiles(array $tiles)
    {
        foreach ($tiles as $tile_index => &$tile) {
            foreach ($tile['controllers'] as $ctlr => $name) {
                if (!AdminPerms::controllerAccess($ctlr, 'contents')) {
                    unset($tile['controllers'][$ctlr]);
                }
            }
            if (count($tile['controllers']) === 0) {
                unset($tiles[$tile_index]);
            }
        }

        return $tiles;
    }


    /**
     * Return the list of operator categories which the currently-logged in operator can manage
     * @return array Categories as id => name
     */
    public static function getManageOperatorCategories()
    {
        if (! AdminAuth::isLoggedIn()) {
            return array();
        }

        $cats_table = Category::tableMain2cat('operators');

        if (AdminPerms::canAccess('access_operators')) {
            try {
                return Pdb::lookup($cats_table);
            } catch (QueryException $ex) {
                // Assume DB has no tables; can't manage non-existent categories
                return [];
            }
        }

        $joiner_table = Category::tableMain2joiner('operators');

        // Get the list
        $q = "SELECT cat.id, cat.name
            FROM ~{$cats_table} AS cat
            INNER JOIN ~operatorcategory_manage_categories AS manage ON manage.manage_category_id = cat.id
            INNER JOIN ~{$joiner_table} AS joiner ON manage.operatorcategory_id = joiner.cat_id
            WHERE joiner.operator_id = ?
            ORDER BY cat.name";
        return Pdb::q($q, [AdminAuth::getId()], 'map');
    }


    /**
    * Can the currently-logged in operator edit the operator in question?
    **/
    public static function canEditOperator($operator_id)
    {
        if (! AdminAuth::isLoggedIn()) {
            return false;
        }

        if (AdminPerms::canAccess('access_operators')) {
            return true;
        }

        // Get list of categories the operator is in
        $record_cats = Category::categoryList('operators', $operator_id);
        if (! $record_cats) return false;

        // Get list of categories the logged in operator is allowed to manage
        $manage_cats = self::getManageOperatorCategories();
        if (! $manage_cats) return false;

        // Check for a match between them
        foreach ($manage_cats as $id => $name) {
            if (in_array($id, $record_cats)) return true;
        }

        return false;
    }

}


