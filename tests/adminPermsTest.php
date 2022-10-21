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

use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Pdb;


class adminPermsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Create a page structure in memory (SQLite)
     *         [1]           [6]       [9]
     *   [2] [3] [4] [5]   [7] [8]  [10] [11]
     */
    protected function setUp()
    {
        $pages = [
            ['id' => 1, 'parent_id' => 0, 'admin_perm_type' => Constants::PERM_INHERIT],    // top-level, all
            ['id' => 2, 'parent_id' => 1, 'admin_perm_type' => Constants::PERM_INHERIT],    // inherit, all
            ['id' => 3, 'parent_id' => 1, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // no perms
            ['id' => 4, 'parent_id' => 1, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // group 1
            ['id' => 5, 'parent_id' => 1, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // group 2

            ['id' => 6, 'parent_id' => 0, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // top-level, none
            ['id' => 7, 'parent_id' => 6, 'admin_perm_type' => Constants::PERM_INHERIT],    // inherit, none
            ['id' => 8, 'parent_id' => 6, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // specific, group 1

            ['id' => 9, 'parent_id' => 0, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // top-level, group 1
            ['id' => 10,'parent_id' => 9, 'admin_perm_type' => Constants::PERM_INHERIT],    // inherit, group 1
            ['id' => 11,'parent_id' => 9, 'admin_perm_type' => Constants::PERM_SPECIFIC],   // specific, group 2
        ];
        $cats = [
            ['id' => 1, 'name' => 'Group one'],
            ['id' => 2, 'name' => 'Group two'],
        ];
        $perms = [
            ['item_id' => 4, 'category_id' => 1],
            ['item_id' => 5, 'category_id' => 2],
            ['item_id' => 8, 'category_id' => 1],
            ['item_id' => 9, 'category_id' => 1],
            ['item_id' => 11,'category_id' => 2],
        ];

        $sqlite = new PDO('sqlite::memory:');
        Pdb::setOverrideConnection($sqlite);

        Pdb::query('CREATE TABLE ~pages (id INT, parent_id INT, admin_perm_type INT)', [], 'null');
        foreach ($pages as $row) {
            Pdb::insert('pages', $row);
        }

        Pdb::query('CREATE TABLE ~operators_cat_list (id INT, name VARCHAR(100))', [], 'null');
        foreach ($cats as $row) {
            Pdb::insert('operators_cat_list', $row);
        }

        Pdb::query('CREATE TABLE ~page_admin_permissions (item_id INT, category_id INT)', [], 'null');
        foreach ($perms as $row) {
            Pdb::insert('page_admin_permissions', $row);
        }
    }

    protected function tearDown()
    {
        Pdb::clearOverrideConnection();
    }


    public function dataGetAccessableGroups()
    {
        return [
            [0, [1, 2]],
            [1, [1, 2]],
            [2, [1, 2]],
            [3, []],
            [4, [1]],
            [5, [2]],
            [6, []],
            [7, []],
            [8, [1]],
            [9, [1]],
            [10,[1]],
            [11,[2]],
            [99999, false],
        ];
    }


    /**
     * @dataProvider dataGetAccessableGroups
     */
    public function testGetAccessableGroups($page_id, $expected)
    {
        $this->assertEquals($expected, AdminPerms::getAccessableGroups('pages', $page_id));
    }

}
