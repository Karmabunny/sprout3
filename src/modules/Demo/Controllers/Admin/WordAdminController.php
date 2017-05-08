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
use Sprout\Helpers\ColModifierBinary;


/**
 * Handles admin processing for Words
 */
class WordAdminController extends HasCategoriesAdminController
{
    protected $controller_name = 'word';
    protected $friendly_name = 'Words';
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
        ];

        $this->initRefineBar();

        parent::__construct();
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

}


