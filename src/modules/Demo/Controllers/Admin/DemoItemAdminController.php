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

namespace SproutModules\Karmabunny\Demo\Controllers\Admin;

use InvalidArgumentException;

use Sprout\Controllers\Admin\HasCategoriesAdminController;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Json;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Url;
use Sprout\Helpers\WorkerCtrl;


/**
 * Handles admin processing for Demo items
 */
class DemoItemAdminController extends HasCategoriesAdminController
{
    protected $controller_name = 'demo_item';
    protected $friendly_name = 'Demo items';
    protected $add_defaults = [
        'active' => 1,
    ];
    protected $main_columns = [];
    protected $main_delete = true;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Active' => [new ColModifierBinary(), 'active'],
            'Email' => 'email',
        ];

        $this->initRefineBar();

        parent::__construct();
    }


    public function _getTools()
    {
        $tools = parent::_getTools();

        $url = "admin/call/{$this->controller_name}/runWorker";
        $tools['worker'] = '<li class="worker"><a href="' . Enc::html($url) . '">Run demo worker</a></li>';

        return $tools;
    }


    /**
    * Pre-render hook for adding
    **/
    protected function _addPreRender($view)
    {
        parent::_addPreRender($view);
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
     */
    public function _addSave(&$item_id)
    {
        return parent::_addSave($item_id);
    }



    /**
    * Pre-render hook for editing
    **/
    protected function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);
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
     * Process the saving of a record.
     *
     * @param int $item_id The record to save
     * @return boolean True on success, false on failure
     */
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;
        if ($item_id <= 0) throw new InvalidArgumentException('$item_id must be greater than 0');

        return parent::_editSave($item_id);
    }


    /**
     * AJAX lookup of the words table for the 'word' field
     * @return string AJAX array containing required 'value' key for each item
     */
    public function ajaxLookup() {
        AdminAuth::checkLogin();

        if (!empty($_GET['id'])) {
            $q = "SELECT name AS label FROM ~words WHERE id = ?";
            $res = Pdb::q($q, [$_GET['id']], 'arr');
            Json::out($res);
        }

        $q = "SELECT id, name AS value
            FROM ~words
            WHERE name LIKE CONCAT(?, '%')";
        $res = Pdb::q($q, [Pdb::likeEscape($_GET['term'])], 'arr');
        Json::out($res);
    }


    /**
     * AJAX lookup of the words table for the autofill list
     * @return string AJAX array containing required 'value' key for each item
     */
    public function autofillLookup() {
        AdminAuth::checkLogin();

        $q = "SELECT id, CONCAT('#', id, ' - ', name) AS value
            FROM ~words
            WHERE name LIKE CONCAT(?, '%')";
        $res = Pdb::q($q, [Pdb::likeEscape($_GET['term'])], 'arr');
        Json::out($res);
    }


    public function runWorker()
    {
        $worker = WorkerCtrl::start('SproutModules\\Karmabunny\\Demo\\Helpers\\DemoWorker', 100, 500, 1000);
        Url::redirect($worker['log_url']);
    }
}


