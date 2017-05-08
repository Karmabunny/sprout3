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

namespace SproutModules\Karmabunny\HomePage\Controllers\Admin;

use Sprout\Controllers\Admin\ManagedAdminController;
use Sprout\Controllers\Admin\PageAdminController;
use Sprout\Helpers\Pdb;


/**
* Showing and editing of the home page
**/
class HomePageAdminController extends ManagedAdminController
{
    protected $controller_name = 'home_page';
    protected $friendly_name = 'Home page';
    protected $navigation_name = 'Pages';
    protected $table_name = 'homepages';
    protected $main_delete = false;


    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     * @return array Key is the field name, value is the field label
     */
    public function _getVisibilityFields()
    {
        return [];
    }


    public function _identifier(array $row)
    {
        return Pdb::q("SELECT name FROM ~subsites WHERE id = ?", [$row['subsite_id']], 'val');
    }


    /**
    * Proxies navigation to the 'page' controller.
    **/
    public function _getNavigation()
    {
        $pages = new PageAdminController();
        return $pages->_getNavigation();
    }


    /**
    * Proxies tools to the 'page' controller.
    **/
    public function _getTools()
    {
        $ctlr = new PageAdminController();
        return $ctlr->_getTools();
    }


    /**
    * Gets the name of the controller to use for the top nav
    **/
    public function getTopnavName()
    {
        return 'page';
    }


    public function _getAddForm() { return null; }
    public function _addSave(&$id) { return null; }
}


