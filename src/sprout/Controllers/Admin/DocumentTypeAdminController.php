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

use InvalidArgumentException;

use Sprout\Helpers\Pdb;


/**
 * Handles admin processing for Document types
 */
class DocumentTypeAdminController extends ListAdminController
{
    protected $friendly_name = 'Document types';
    protected $add_defaults = [];
    protected $main_columns = [];


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
        ];

        $this->initRefineBar();

        parent::__construct();
    }


    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     * @return array Key is the field name, value is the field label
     */
    public function _getVisibilityFields()
    {
        return [];
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
     * @return bool|string True on success, false on failure, or a redirect URL
     */
    public function _addSave(&$item_id)
    {
        Pdb::transact();
        if (!parent::_addSave($item_id)) return false;

        $this->fixRecordOrder($item_id);
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
        // Add your actions here, like this: $actions[] = [ ... ];
        return $actions;
    }


    /**
     * Saves the provided POST data into the specified record
     *
     * @param int $item_id The record to update
     * @return bool|string True on success, false on failure, or a redirect URL
     */
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;
        if ($item_id <= 0) throw new InvalidArgumentException('$item_id must be greater than 0');

        return parent::_editSave($item_id);
    }

}
