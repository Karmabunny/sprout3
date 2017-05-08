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

use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;


/**
* Handles most processing for Subsites
**/
class SubsiteAdminController extends ListAdminController
{
    protected $controller_name = 'subsite';
    protected $friendly_name = 'Subsites';
    protected $action_log = true;

    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Code' => 'code',
        ];

        parent::__construct();
    }


    /**
    * Validates incoming POST data.
    *
    * @param bool True on success, false on failure
    **/
    private function validate()
    {
        $_SESSION['admin']['field_values'] = Validator::trim($_POST);
        $result = true;

        $valid = new Validator($_POST);
        $valid->required(['name', 'code']);
        $valid->check('name', 'Validity::length', 0, 50);
        $valid->check('code', 'Validity::length', 0, 15);
        $valid->check('cond_directory', 'Validity::length', 0, 150);
        $valid->check('override_site_title', 'Validity::length', 0, 150);

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            $result = false;
        }

        return $result;
    }


    public function _addPreRender($view)
    {
        parent::_addPreRender($view);

        $view->codes = Subsites::getCodes();
        $view->subsites = Pdb::lookup('subsites', ['content_id' => 0]);
    }


    /**
     * Return the sub-actions for adding; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getAddSubActions()
    {
        $actions = parent::_getAddSubActions();
        // Add your actions here, like this: $actions[] = [ ... ];
        return $actions;
    }


    /**
    * Saves the provided POST data into a new record in the database
    *
    * @param int $item_id After saving, the new record id will be returned in this parameter
    * @param bool True on success, false on failure
    **/
    public function _addSave(&$item_id)
    {
        $res = $this->validate();
        if (! $res) return false;

        Pdb::transact();

        // Main insert
        $update_fields = [];
        $update_fields['name'] = $_POST['name'];
        $update_fields['code'] = $_POST['code'];
        $update_fields['cond_domain'] = $_POST['cond_domain'];
        $update_fields['cond_directory'] = $_POST['cond_directory'];
        $update_fields['mobile'] = (int) (bool) @$_POST['mobile'];
        $update_fields['content_id'] = $_POST['content_id'];
        $update_fields['require_admin'] = (int) (bool) @$_POST['require_admin'];
        $update_fields['require_user'] = (int) (bool) @$_POST['require_user'];
        $update_fields['active'] = $_POST['active'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $item_id = Pdb::insert('subsites', $update_fields);

        $res = $this->logAdd('subsites', $item_id);

        // Create homepage record
        $update_fields = array();
        $update_fields['subsite_id'] = $item_id;
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        Pdb::insert('homepages', $update_fields);

        Pdb::commit();

        return true;
    }


    public function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);

        $view->codes = Subsites::getCodes();
        $view->subsites = Pdb::lookup('subsites', ['content_id' => 0, ['id', '!=', $item_id]]);
    }


    /**
     * Return the sub-actions for editing; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);
        // Add your actions here, like this: $actions[] = [ ... ];
        return $actions;
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

        $res = $this->validate();
        if (! $res) return false;

        // Start transaction
        $res = Pdb::transact();

        // Update item
        $update_fields = [];
        $update_fields['name'] = $_POST['name'];
        $update_fields['code'] = $_POST['code'];
        $update_fields['cond_domain'] = $_POST['cond_domain'];
        $update_fields['cond_directory'] = $_POST['cond_directory'];
        $update_fields['mobile'] = (int) (bool) @$_POST['mobile'];
        $update_fields['content_id'] = (int) @$_POST['content_id'];
        $update_fields['require_admin'] = (int) (bool) @$_POST['require_admin'];
        $update_fields['require_user'] = (int) (bool) @$_POST['require_user'];
        $update_fields['active'] = $_POST['active'];
        $update_fields['date_modified'] = Pdb::now();

        $logdata = $this->loadRecord('subsites', $item_id);

        Pdb::update('subsites', $update_fields, ['id' => $item_id]);

        $this->logEdit('subsites', $item_id, $logdata);

        // Commit
        Pdb::commit();

        return true;
    }


    /**
    * Return HTML which represents the form for deleting a record
    *
    * @param int $id The record to show the delete form for
    * @return string The HTML code which represents the edit form
    **/
    public function _getDeleteForm($id)
    {
        $q = "SELECT COUNT(id) FROM ~subsites";
        $count = Pdb::q($q, [], 'val');
        if ($count == 1) {
            Notification::error('You cannot delete the only subsite in the system; must have at least one subsite at all times');
            Url::redirect('admin/contents/subsite');
        }

        return parent::_getDeleteForm($id);
    }


    /**
     * Prevents deletion if there's only subsite
     * @param int $item_id The record to delete
     * @return void
     * @throws Exception if the deletion shouldn't proceed for some reason
     */
    public function _deletePreSave($item_id)
    {
        $q = "SELECT COUNT(id) FROM ~subsites";
        $count = Pdb::q($q, [], 'val');
        if ($count == 1) {
            throw new Exception('You cannot delete the only subsite in the system; must have at least one subsite at all times');
        }
    }

}
