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

namespace Sprout\Services;

use Sprout\Helpers\BaseView;

/**
 * Provides user permission functions for the front-end
 *
 * @package Sprout\Services
 */
interface UserPermsInterface extends ServiceInterface
{

    /**
     * Checks whether the currently logged in user can access the specified item.
     * This method should be used for tree-based tables, like the 'pages' table,
     * which may inherit their permissions from the parent record.
     *
     * @param string $table The table name of the item to check
     * @param int $id The id of the record to check
     * @return bool True if the user has access, false otherwise
     */
    public static function checkPermissionsTree(string $table, int $id);


    /**
     * Returns a list of groups which can access a specific page.
     *
     * @param string $table The table to get permissions for
     * @param int $id The ID of the record to get permissions for
     * @param bool $is_parent
     * @return array Each element is a category id
     */
    public static function getAccessableGroups(string $table, int $id);


    /**
     * Return a message which is output to a user when access is denied
     *
     * @return BaseView|null
     */
    public static function getAccessDenied(): ?BaseView;


    /**
     * Gets a list of all of the user categories
     *
     * @return array [id => name]
     */
    public static function getAllCategories(): array;

}
