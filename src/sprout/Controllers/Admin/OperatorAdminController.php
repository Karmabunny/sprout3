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

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Email;
use Sprout\Helpers\EmailText;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Security;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;


/**
* Handles most processing for operators
**/
class OperatorAdminController extends HasCategoriesAdminController
{
    /**
     * The maximum permissable password length; this is likely limited by the hash method used
     * Currently set to bcrypt's truncate length of 72
     */
    const MAX_PASSWORD_LENGTH = 72;

    protected $controller_name = 'operator';
    protected $friendly_name = 'Operators';
    protected $add_defaults = array(
        'active' => 1,
    );
    protected $action_log = true;


    /**
    * The categories which can be edited by the logged in user
    **/
    private $manage_cats;


    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Username' => 'username',
            'Email' => 'email',
        ];

        $this->manage_cats = AdminPerms::getManageOperatorCategories();

        if (!AdminPerms::canAccess('access_operators')) {
            $cats = implode(',', array_keys($this->manage_cats));
            $this->main_where[] = "(SELECT 1 FROM ~operators_cat_join WHERE operator_id = item.id AND cat_id IN ({$cats}) LIMIT 1) = 1";
        }

        parent::__construct();
    }


    /**
    * Show tools - but only for full access ops
    **/
    public function _getTools()
    {
        if (! AdminPerms::canAccess('access_operators')) return array();

        $tools = parent::_getTools();

        $tools[] = '<li class="config"><a href="' . EmailText::adminEditUrl('operator.welcome') . '">Edit welcome message email</a></li>';
        $tools[] = sprintf('<li class="config"><a href="admin/call/%s/unlockOperator/0">Clear all login locks</a></li>', Enc::html($this->controller_name));

        return $tools;
    }


    /**
    * Get contents list - but only for partial access ops
    **/
    public function _getContents()
    {
        if (count($this->manage_cats) == 0) {
            return new AdminError('Access denied');
        }

        return parent::_getContents();
    }


    /**
    * Get add form - but only for partial access ops
    **/
    public function _getAddForm()
    {
        if (count($this->manage_cats) == 0) {
            return new AdminError('Access denied');
        }

        return parent::_getAddForm();
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
    * Get edit form - but only for partial access ops or for editing your own record
    **/
    public function _getEditForm($item_id)
    {
        if (!AdminPerms::canEditOperator($item_id) and $item_id != AdminAuth::getId()) {
            return new AdminError('Access denied');
        }

        return parent::_getEditForm($item_id);
    }


    /**
     * Return the sub-actions for editing a record (e.g. deleting)
     * These are rendered into HTML using {@see AdminController::renderSubActions}
     *
     * @return array Each key is a unique reference to the action, e.g. 'delete', and the value is an array, with keys:
     *         url => URL to link to, e.g. "admin/delete/thing/$item_id"
     *         name => Label to display to the user, e.g. 'Delete'
     *         class => CSS class(es) for the icon, e.g. 'icon-link-button icon-before icon-delete'
     *         new_tab => True to show in new window/tab (optional; defaults to false)
     */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);

        $actions['unlock'] = [
            'url' => "admin/call/{$this->controller_name}/unlockOperator/{$item_id}",
            'name' => 'Clear Login Locks',
            'class' => 'icon-link-button icon-before icon-security',
        ];

        return $actions;
    }


    /**
    * Get delete form - but only for partial access ops
    **/
    public function _getDeleteForm($item_id)
    {
        if (!AdminPerms::canEditOperator($item_id)) {
            return new AdminError('Access denied');
        }

        if ($item_id == AdminAuth::getLocalId()) {
            return new AdminError('You cannot delete your own record');
        }

        return parent::_getDeleteForm($item_id);
    }



    /**
    * Pre-render hook for adding
    **/
    protected function _addPreRender($view)
    {
        parent::_addPreRender($view);

        $view->cats = $this->manage_cats;
    }


    /**
    * Saves the provided POST data into a new record in the database
    *
    * @param int $item_id After saving, the new record id will be returned in this parameter
    * @return bool True on success, false on failure
    **/
    public function _addSave(&$item_id)
    {
        if (count($this->manage_cats) == 0) return false;

        $_SESSION['admin']['field_values'] = Validator::trim($_POST);
        $result = true;

        $valid = new Validator($_POST);
        $valid->required(['name', 'email', 'username', 'password1', 'password2']);
        $valid->check('name', 'Validity::length', 0, 200);
        $valid->check('email', 'Validity::email');
        $valid->check('email', 'Validity::length', 0, 200);
        $valid->check('username', 'Validity::length', 0, 50);
        $valid->check('username', 'Validity::uniqueValue', 'operators', 'username', 0, 'An operator already exists with that username');
        $valid->check('username', 'Validity::regex', '/^[a-zA-Z0-9]+$/');
        $valid->check('password1', 'Validity::length', 8, self::MAX_PASSWORD_LENGTH);
        $valid->check('password2', 'Validity::length', 8, self::MAX_PASSWORD_LENGTH);
        $valid->multipleCheck(['password1', 'password2'], 'Validity::allMatch');

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $result = false;
        }

        if ($_POST['password1'] and $_POST['password1'] === $_POST['password2']) {
            // Check password is complex enough
            $complexity = Security::passwordComplexity($_POST['password1'], 8, 2, true);
            if (!empty($complexity)) {
                $_SESSION['admin']['field_errors']['password1'] = 'Not complex enough';
                $_SESSION['admin']['field_errors']['password2'] = 'Not complex enough';
                $result = false;

                Notification::error('Password does not meet complexity requirements:');
                foreach ($complexity as $c) {
                    Notification::error(" \xC2\xA0 \xC2\xA0 " . $c);
                }
            }
        }

        // Check all categories are allowed
        if (empty($_POST['categories'])) {
            Notification::error('You must choose at least one category');
            $result = false;

        } else {
            foreach ($_POST['categories'] as $cat_id) {
                if (!$this->manage_cats[$cat_id]) {
                    Notification::error('You do not have access to manage category id ' . $cat_id);
                    $result = false;
                }
            }
        }

        if (! $result) return false;


        // Start transaction
        Pdb::transact();

        // Main insert
        $update_fields = [];
        $update_fields['name'] = $_POST['name'];
        $update_fields['username'] = $_POST['username'];
        $update_fields['email'] = $_POST['email'];
        $update_fields['firstrun'] = 1;
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();

        $item_id = Pdb::insert('operators', $update_fields);

        $this->logAdd('operators', $item_id);

        AdminAuth::changePassword($_POST['password1'], $item_id);

        // Update the categories
        $this->updateCategories($item_id, $_POST['categories']);

        // Commit
        Pdb::commit();

        // Send email, if message is set
        $_POST['password'] = $_POST['password1'];
        $text = EmailText::getHtml('operator.welcome', $_POST);
        if (trim(strip_tags($text)) != '') {
            $mail = new Email();
            $mail->AddAddress($_POST['email']);
            $mail->Subject = 'New operator account created';
            $mail->SkinnedHTML($text);
            $mail->Send();
        }

        return true;
    }


    /**
    * Pre-render hook for editing
    **/
    protected function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);

        if (AdminAuth::hasDatabaseRecord() and $item_id == AdminAuth::getId()) {
            Url::redirect('admin/intro/my_settings');
        }

        $view->cats = $this->manage_cats;

        foreach ($view->data['categories'] as $cat_id) {
            if (!$this->manage_cats[$cat_id]) {
                Notification::error('This operator is in a category you do not have permission to manage - category id ' . $cat_id);
                Url::redirect('admin/intro/operator');
            }
        }
    }


    /**
    * Saves the provided POST data the specified record
    *
    * @param int $item_id The record to update
    * @return bool True on success, false on failure
    **/
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;

        if (AdminAuth::hasDatabaseRecord() and $item_id == AdminAuth::getId()) {
            Url::redirect('admin/intro/my_settings');
        }

        $can_access = AdminPerms::canEditOperator($item_id);
        if (!$can_access) return false;

        $_SESSION['admin']['field_values'] = Validator::trim($_POST);
        $result = true;

        $valid = new Validator($_POST);
        $valid->required(['username', 'name', 'email']);
        $valid->check('username', 'Validity::length', 0, 50);
        $valid->check('username', 'Validity::uniqueValue', 'operators', 'username', $item_id, 'An operator already exists with that username');
        $valid->check('username', 'Validity::regex', '/^[a-zA-Z0-9]+$/');
        $valid->check('name', 'Validity::length', 0, 200);
        $valid->check('email', 'Validity::length', 0, 200);
        $valid->check('password1', 'Validity::length', 8, self::MAX_PASSWORD_LENGTH);
        $valid->check('password2', 'Validity::length', 8, self::MAX_PASSWORD_LENGTH);
        $valid->multipleCheck(['password1', 'password2'], 'Validity::allMatch');

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            $result = false;
        }

        if ($_POST['password1'] and $_POST['password1'] === $_POST['password2']) {
            // Check password is complex enough
            $complexity = Security::passwordComplexity($_POST['password1'], 8, 2, true);
            if (!empty($complexity)) {
                $_SESSION['admin']['field_errors']['password1'] = 'Not complex enough';
                $_SESSION['admin']['field_errors']['password2'] = 'Not complex enough';
                $result = false;

                Notification::error('Password does not meet complexity requirements:');
                foreach ($complexity as $c) {
                    Notification::error(" \xC2\xA0 \xC2\xA0 " . $c);
                }
            }
        }

        // Check all categories are allowed
        if (empty($_POST['categories'])) {
            Notification::error('You must choose at least one category');
            $result = false;

        } else {
            foreach ($_POST['categories'] as $cat_id) {
                if (!$this->manage_cats[$cat_id]) {
                    Notification::error('You do not have access to manage category id ' . $cat_id);
                    $result = false;
                }
            }
        }

        if (! $result) return false;

        // Start transaction
        Pdb::transact();

        // Update item
        $update_fields = array();
        $update_fields['name'] = $_POST['name'];
        $update_fields['email'] = $_POST['email'];
        $update_fields['active'] = (int) @$_POST['active'];
        $update_fields['username'] = $_POST['username'];
        $update_fields['date_modified'] = Pdb::now();

        $logdata = $this->loadRecord('operators', $item_id);

        Pdb::update('operators', $update_fields, ['id' => $item_id]);

        $this->logEdit('operators', $item_id, $logdata);

        if ($_POST['password1']) {
            AdminAuth::changePassword($_POST['password1'], $item_id);
            $this->logAction('operators', $item_id, 'Change password');
        }

        // Update the categories
        $this->updateCategories($item_id, $_POST['categories']);

        // Commit
        Pdb::commit();

        return true;
    }


    /**
     * Prevents deletion of accounts when the user doesn't have access, and deletion of self
     * @param int $item_id The record to delete
     * @return void
     * @throws Exception if deletion not allowed
     */
    public function _deletePreSave($item_id)
    {
        $item_id = (int) $item_id;

        if (!AdminPerms::canEditOperator($item_id)) {
            throw new Exception('Permission denied');
        }

        if ($item_id == AdminAuth::getLocalId()) {
            throw new Exception('You cannot delete your own record');
        }
    }


    /**
     * Remove an operator login lock by deactivating recent failed attempts in db
     * @param int $item_id Operator ID
     * @return void Redirects
     */
    public function unlockOperator($item_id)
    {
        $item_id = (int) $item_id;
        $redirect = 'admin/intro/operator/';
        $username = 'All operators';
        $conditions = [];
        $params = [];

        $conditions[] = ['date_added', '>', date('Y-m-d H:i:s', time() - AdminAuth::LOGIN_LIMIT_SECONDS)];

        if ($item_id)
        {
            $redirect = "admin/edit/operator/{$item_id}";
            $operator = Pdb::get('operators', $item_id);
            $username = $operator['username'];
            $conditions[] = ['username', '=', $username];
        }

        $where = Pdb::buildClause($conditions, $params);

        $q = "UPDATE ~login_attempts SET active = 0 WHERE {$where}";
        $num = Pdb::query($q, $params, "count");

        Notification::confirm("{$num} login attempts cleared. {$username} unlocked");
        Url::redirect($redirect);
    }
}
