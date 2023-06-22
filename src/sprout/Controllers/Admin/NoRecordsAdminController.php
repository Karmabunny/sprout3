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


/**
 * Provides a base class for controllers which don't manage records, but are just a set of tools
 */
abstract class NoRecordsAdminController extends ManagedAdminController
    implements NoRecordPermissionsInterface
{

    public function _getNavigation() { return ''; }
    public function _getTools() { return []; }
    public function _getContents()
    {
        throw new \BadFunctionCallException('Method implementation needed');
    }

    public function _getAddForm()
    {
        return [
            'title' => 'Not permitted',
            'content' => ''
        ];
    }

    public function _getEditForm($item_id)
    {
        return [
            'title' => 'Not permitted',
            'content' => ''
        ];
    }

    public function _getDeleteForm($item_id) { return ''; }

    public function _addSave(&$item_id) { return false; }
    public function _editSave($item_id) { return false; }
    public function _deleteSave($item_id) { return false; }

}
