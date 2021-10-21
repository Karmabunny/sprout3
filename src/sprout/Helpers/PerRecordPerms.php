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

use Sprout\Controllers\Admin\ManagedAdminController;
use karmabunny\pdb\Exceptions\RowMissingException;


/**
 * Helper for implementing per-record permissions
 */
class PerRecordPerms
{
    /**
     * Checks to see if per-record permissions apply to a particular controller
     *
     * @param ManagedAdminController $ctlr The controller to check
     * @return bool True if per-record permissions apply
     */
    public static function controllerRestricted(ManagedAdminController $ctlr)
    {
        $controller_name = $ctlr->getControllerName();

        $q = "SELECT 1 FROM ~per_record_controllers WHERE name = ? AND active = 1";
        return (bool) Pdb::query($q, [$controller_name], 'count');
    }


    /**
     * Checks if a given controller has at least one permission rule in place
     *
     * @param ManagedAdminController $ctlr The controller to check
     * @return bool True if at least one permission rule exists
     */
    public static function hasRecordPerms(ManagedAdminController $ctlr)
    {
        $controller_name = $ctlr->getControllerName();

        $q = "SELECT id FROM ~per_record_permissions WHERE controller = ? LIMIT 1";
        return (bool) Pdb::query($q, [$controller_name], 'count');
    }


    /**
     * Gets a clause to restrict a query to the set of categories the current admin belongs to
     *
     * @return string Example: "(operator_categories = '*' OR operator_categories LIKE '%,123,%')"
     */
    public static function getCategoryClause()
    {
        $operator_cats = AdminAuth::getOperatorCategories();

        $clauses = [];
        $clauses[] = "operator_categories = '*'";
        foreach ($operator_cats as $cat_id) {
            $cat_id = (int) $cat_id;
            $clauses[] = "operator_categories LIKE CONCAT('%,{$cat_id},%')";
        }

        return '(' . implode(' OR ', $clauses) . ')';
    }


    /**
     * Gets the permission details from the database
     *
     * @param ManagedAdminController $ctlr Admin controller for the record in question
     * @param int $item_id ID of the record
     * @return array Contains the following elements:
     *         int id
     *         array[int]|string categories (ids as int, or the string '*' for all)
     * @throws RowMissingException if a row containing the permission data wasn't found
     */
    public static function fetchDetails(ManagedAdminController $ctlr, $item_id)
    {
        $q = "SELECT id, operator_categories AS categories
            FROM ~per_record_permissions
            WHERE controller = ? AND item_id = ?";
        $row = Pdb::q($q, [$ctlr->getControllerName(), $item_id], 'row');

        if ($row['categories'] != '*') {
            $row['categories'] = explode(',', trim($row['categories'], ','));
        }

        return $row;
    }


    /**
     * Save the permissions for a particular record
     *
     * @post array[int] _prm_categories IDs of operator categories which can edit the record
     * @post bool _prm_all_cats If true, override _prm_categories; allow all operator categories to edit the record
     * @param ManagedAdminController $ctlr Admin controller for the record in question
     * @param int $item_id ID of the record
     * @return void
     */
    public static function save(ManagedAdminController $ctlr, $item_id)
    {
        if (!PerRecordPerms::controllerRestricted($ctlr)) {
            return;
        }

        $perms = [
            'controller' => $ctlr->getControllerName(),
            'item_id' => $item_id,
            'operator_categories' => '*',
        ];
        if (AdminPerms::canAccess('access_operators')) {
            $all_cat_ids = array_keys(AdminAuth::getAllCategories());
        } else {
            $all_cat_ids = AdminAuth::getOperatorCategories();
        }

        $cat_ids = @$_POST['_prm_categories'];
        if (!is_array($cat_ids)) $cat_ids = [];

        $cat_ids = array_intersect($cat_ids, $all_cat_ids);

        // Always include primary administrators
        try {
            $cat_ids[] = AdminAuth::getPrimaryCategoryId();
        } catch (RowMissingException $ex) {
        }

        $perms['operator_categories'] = ',' . implode(',', $cat_ids) . ',';

        if (AdminPerms::canAccess('access_operators') and !empty($_POST['_prm_all_cats'])) {
            $perms['operator_categories'] = '*';
        }

        $params = [];
        $conds = [
            'controller' => $perms['controller'],
            'item_id' => $item_id,
        ];
        $where = Pdb::buildClause($conds, $params);
        $q = "SELECT id FROM ~per_record_permissions WHERE {$where}";
        try {
            $perm_id = Pdb::q($q, $params, 'val');
            Pdb::update('per_record_permissions', $perms, ['id' => $perm_id]);
        } catch (RowMissingException $ex) {
            Pdb::insert('per_record_permissions', $perms);
        }
    }
}
