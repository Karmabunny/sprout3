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

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\ColModifierLookupArray;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\Register;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;


/**
* Handles most processing for Extra pages
**/
class ExtraPageAdminController extends ManagedAdminController
{
    protected $controller_name = 'extra_page';
    protected $friendly_name = 'Snippet pages';
    protected $add_defaults = array(
        'active' => 1,
    );


    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        $this->extra_page_types = Register::getExtraPages();

        $this->main_order = 'item.type';
        $this->main_columns = array(
            'Type' => array(new ColModifierLookupArray($this->extra_page_types), 'type'),
        );
        $this->main_where = array(
            'subsite_id = ' . ((int)$_SESSION['admin']['active_subsite']),
        );

        $this->refine_bar = new RefineBar();
        $this->refine_bar->addWidget(new RefineWidgetSelect('type', 'Type', $this->extra_page_types));
    }


    /**
    * No tools
    **/
    public function _getTools()
    {
        return null;
    }


    /**
    * Returns the contents of the navigation pane for the list
    **/
    public function _getNavigation()
    {
        $q = "SELECT id, type
            FROM ~extra_pages
            WHERE subsite_id = ?
            ORDER BY type";
        $res = Pdb::query($q, [$_SESSION['admin']['active_subsite']], 'pdo');

        $snippets = [];
        foreach ($res as $row) {
            if (isset($this->extra_page_types[$row['type']])) {
                $snippets[$row['id']] = $this->extra_page_types[$row['type']];
            }
        }

        $view = new PhpView('sprout/admin/extra_page_sidebar');
        $view->snippets = $snippets;
        return $view->render();
    }


    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     *
     * Key is the field name, value is the field label
     *
     * @return array
     */
    public function _getVisibilityFields()
    {
        return [];
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
        $valid->required(['type', 'text']);

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            $result = false;
        }

        return $result;
    }


    /**
    * Pre-render hook for adding
    **/
    protected function _addPreRender($view)
    {
        $types = Register::getExtraPages();

        $q = "SELECT type
            FROM ~extra_pages
            WHERE subsite_id = ?";
        $extant_types = Pdb::q($q, [$_SESSION['admin']['active_subsite']], 'col');
        foreach ($extant_types as $type_id) {
            unset($types[$type_id]);
        }

        $view->types = $types;
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

        // Start transaction
        Pdb::transact();

        // Main insert
        $update_fields = [];
        $update_fields['type'] = $_POST['type'];
        $update_fields['text'] = $_POST['text'];
        $update_fields['subsite_id'] = $_SESSION['admin']['active_subsite'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();

        $item_id = Pdb::insert('extra_pages', $update_fields);

        $res = $this->logAdd('extra_pages', $item_id);
        if (! $res) return false;

        // Commit
        Pdb::commit();

        return true;
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

        if (!AdminAuth::isSuper()) {
            unset($actions['delete']);
        }

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
        Pdb::transact();

        // Update item
        $update_fields = [];
        $update_fields['type'] = $_POST['type'];
        $update_fields['text'] = $_POST['text'];
        $update_fields['date_modified'] = Pdb::now();

        $logdata = $this->loadRecord('extra_pages', $item_id);

        Pdb::update('extra_pages', $update_fields, ['id' => $item_id]);

        $res = $this->logEdit('extra_pages', $item_id, $logdata);
        if (! $res) return false;

        // Commit
        Pdb::commit();

        return true;
    }

    /**
    * Creates the identifier used in the heading.
    *
    * @return string
    **/
    public function _identifier(array $item)
    {
        $labels = Register::getExtraPages();
        return $labels[$item['type']];
    }
}


