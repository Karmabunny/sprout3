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


use Sprout\Controllers\Admin\ListAdminController;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetTextbox;


/**
* Admin UI for managing Sprout Tags
**/
class TagAdminController extends ListAdminController
{
    protected $friendly_name = 'Tags';
    protected $action_log = false;
    protected $main_delete = true;
    protected $main_add = false;
    protected $main_order = 'item.name';


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->main_columns = [
            'Tag' => 'name',
            'Module' => 'record_table',
            'Module\'s record' => 'record_id',
        ];

        parent::__construct();

        $this->refine_bar = new RefineBar();
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Tag'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('record_table', 'Module'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('record_id', 'Record'));
    }


    /**
     * Returns the contents of the navigation pane for the list
     */
    public function _getNavigation()
    {
        return null;
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
     * Returns the tools to show in the left navigation
     */
    public function _getTools()
    {
        return [];
    }


    protected function _preSave($id, &$data)
    {
    }
}
