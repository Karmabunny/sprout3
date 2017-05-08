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

use Sprout\Helpers\EmailText;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Validator;


/**
* Handles most processing for Email text
**/
class EmailTextAdminController extends ManagedAdminController
{
    protected $controller_name = 'email_text';
    protected $friendly_name = 'Email text';
    protected $add_defaults = array(
        'active' => 1,
    );
    protected $main_delete = false;


    public function _getNavigation()
    {
        return null;
    }

    public function _getTools()
    {
        return null;
    }

    public function _getContents()
    {
        return null;
    }

    public function _getAddForm()
    {
        return '<p><i>You cannot add these manually.</i></p>';
    }

    public function _addSave(&$item_id)
    {
        return false;
    }


    /**
    * Pre-render hook for editing
    **/
    protected function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);

        $view->field_defs = EmailText::getFieldDefs($view->data['name']);
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
    * @param int $code The record to update
    * @param bool True on success, false on failure
    **/
    public function _editSave($id)
    {
        $id = (int) $id;

        $_SESSION['admin']['field_values'] = Validator::trim($_POST);

        // Validate form fields
        $valid = new Validator($_POST);
        $valid->required(['text']);
        $valid->check('text', 'Validity::length', 0, 5000);
        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        // Prep changes
        $update_fields = array();
        $update_fields['text'] = $_POST['text'];
        $update_fields['date_modified'] = Pdb::now();

        Pdb::transact();

        $logdata = $this->loadRecord($this->table_name, $id);

        Pdb::update($this->table_name, $update_fields, ['id' => $id]);

        $this->logEdit($this->table_name, $id, $logdata);

        Pdb::commit();

        return true;
    }

}


