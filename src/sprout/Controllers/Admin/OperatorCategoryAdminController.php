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

use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;
use Sprout\Helpers\Validator;
use Sprout\Helpers\Validity;


/**
* Handles most processing for operator categories
**/
class OperatorCategoryAdminController extends CategoryAdminController
{
    protected $controller_name = 'operator_category';
    protected $friendly_name = 'Operator categories';

    /**
     * The view to use for editing existing category records
     */
    protected $edit_view_name = 'sprout/admin/operator_category_edit';


    public function _getAddForm()
    {
        if (! AdminPerms::canAccess('access_operators')) return new AdminError('Access denied');
        return parent::_getAddForm();
    }

    public function _getEditForm($item_id)
    {
        if (! AdminPerms::canAccess('access_operators')) return new AdminError('Access denied');
        return parent::_getEditForm($item_id);
    }

    public function _getDeleteForm($item_id)
    {
        if (! AdminPerms::canAccess('access_operators')) return new AdminError('Access denied');
        return parent::_getDeleteForm($item_id);
    }


    public function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);

        $controllers = Register::getAdminControllers();

        // Remove category controllers, use controller friendly name
        foreach ($controllers as $shorthand => $ctlr_class) {
            $reflect = new \ReflectionClass($ctlr_class);
            if ($reflect->isSubclassOf('Sprout\\Controllers\\Admin\\CategoryAdminController')) {
                unset($controllers[$shorthand]);
                continue;
            }
            $props = $reflect->getDefaultProperties();
            $controllers[$shorthand] = $props['friendly_name'];
        }

        asort($controllers);
        $view->controllers = $controllers;

        // Grab the current ones for the multiedit
        if (! isset($view->data['multiedit_permissions'])) {
            $view->data['multiedit_permissions'] = MultiEdit::load('operatorcategory_permissions', ['operatorcategory_id' => $item_id], 'controller');
        }

        // Get the subsites
        $view->subsites = Pdb::lookup('subsites');

        // Fetch the per-subsite permissions
        $q = "SELECT subsite_id
            FROM ~operatorcategory_subsites
            WHERE ~operatorcategory_subsites.operatorcategory_id = ?";
        $subs_ops = Pdb::q($q, [$item_id], 'arr');

        // Grab the current settings and load into array
        $subsites_permitted = array();
        foreach ($subs_ops as $sub_op) {
            $subsites_permitted[] = $sub_op['subsite_id'];
        }
        $view->data['subsites_permitted'] = $subsites_permitted;

        // Fetch the manage categories
        if (!isset($view->data['manage_categories'])) {
            $q = "SELECT manage_category_id
                FROM ~operatorcategory_manage_categories
                WHERE operatorcategory_id = ?";
            $view->data['manage_categories'] = Pdb::q($q, [$item_id], 'col');
        }
    }

    /**
    * Saves the provided POST data the specified record
    *
    * @param int $item_id The record to update
    * @param bool True on success, false on failure
    **/
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;

        if (! AdminPerms::canAccess('access_operators')) return false;

        unset($_SESSION['admin']['field_errors']);
        $_SESSION['admin']['field_values'] = Validator::trim($_POST);

        $ip_list = preg_split('/,\s*/', trim($_POST['allowed_ips']));
        $ip_list = array_filter($ip_list);

        // Validate
        $valid = new Validator($_POST);
        $valid->setFieldLabel('allowed_ips', 'Restrict access to specific IPs');
        $valid->required(['name']);
        $valid->check('name', 'Validity::length', 0, 200);
        $valid->check('allowed_ips', 'Validity::length', 0, 200);

        foreach ($ip_list as $ip) {
            try {
                Validity::ipv4AddrOrCidr($ip);
            } catch (ValidationException $ex) {
                $err = $ex->getMessage() . ': ' . $ip;
                $valid->addFieldError('allowed_ips', $err);
            }
        }

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        // Start transaction
        $res = Pdb::transact();

        // Update item
        $update_fields = array();
        $update_fields['name'] = $_POST['name'];
        $update_fields['access_operators'] = (int) (bool) @$_POST['access_operators'];
        $update_fields['access_noapproval'] = (int) (bool) @$_POST['access_noapproval'];
        $update_fields['access_reportemail'] = (int) (bool) @$_POST['access_reportemail'];
        $update_fields['access_homepage'] = (int) (bool) @$_POST['access_homepage'];
        $update_fields['default_allow'] = (int) (bool) @$_POST['default_allow'];
        $update_fields['access_all_subsites'] = (int) (bool) @$_POST['access_all_subsites'];
        $update_fields['allowed_ips'] = implode(', ', $ip_list);

        Pdb::update($this->table_name, $update_fields, ['id' => $item_id]);


        // Update the per-tab permissions
        if (@!is_array($_POST['multiedit_permissions'])) $_POST['multiedit_permissions'] = array();

        $new_set = array();
        foreach ($_POST['multiedit_permissions'] as $data) {
            if (MultiEdit::recordEmpty($data)) continue;

            $update_fields = array();
            $update_fields['id'] = (int) $data['id'];
            $update_fields['operatorcategory_id'] = $item_id;
            $update_fields['controller'] = $data['controller'];

            $update_fields['access_contents'] = (int) @$data['access_contents'];
            $update_fields['access_export'] = (int) @$data['access_export'];
            $update_fields['access_report'] = (int) @$data['access_report'];
            $update_fields['access_import'] = (int) @$data['access_import'];
            $update_fields['access_add'] = (int) @$data['access_add'];
            $update_fields['access_edit'] = (int) @$data['access_edit'];
            $update_fields['access_delete'] = (int) @$data['access_delete'];
            $update_fields['access_categories'] = (int) @$data['access_categories'];
            $update_fields['access_reorder'] = (int) @$data['access_reorder'];

            $new_set[] = $update_fields;
        }

        $this->replaceSet('operatorcategory_permissions', $new_set, ['operatorcategory_id' => $item_id]);


        // Update the per-subsite permissions
        if (@!is_array($_POST['subsites_permitted'])) $_POST['subsites_permitted'] = array();

        $new_set = array();
        foreach ($_POST['subsites_permitted'] as $idx => $subsite_id) {
            $update_fields = array();
            $update_fields['subsite_id'] = (int) $subsite_id;
            $update_fields['operatorcategory_id'] = $item_id;
            $update_fields['date_modified'] = Pdb::now();

            $new_set[] = $update_fields;
        }

        $this->replaceSet('operatorcategory_subsites', $new_set, ['operatorcategory_id' => $item_id]);


        // Update the operator management permissions
        if (@!is_array($_POST['manage_categories'])) $_POST['manage_categories'] = array();

        $new_set = array();
        foreach ($_POST['manage_categories'] as $idx => $category_id) {
            $update_fields = array();
            $update_fields['manage_category_id'] = (int) $category_id;
            $update_fields['operatorcategory_id'] = $item_id;
            $update_fields['date_modified'] = Pdb::now();

            $new_set[] = $update_fields;
        }

        $this->replaceSet('operatorcategory_manage_categories', $new_set, ['operatorcategory_id' => $item_id]);


        // Commit
        Pdb::commit();

        return true;
    }
}


