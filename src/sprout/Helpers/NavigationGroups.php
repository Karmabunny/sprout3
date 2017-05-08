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

namespace Sprout\Helpers;

/**
* Menu groups for front end main navigation
**/
class NavigationGroups
{
    private static $fallback_names = array(
        1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven'
    );

    private static $names = array();
    private static $extras = array();


    private static function &loadGroupNames($subsite_id = null) {
        if (! $subsite_id) {
            $subsite_id = SubsiteSelector::$subsite_id;
        }

        if (!isset(self::$names[$subsite_id])) {
            $q = "SELECT id, page_id, position, name
                FROM ~menu_groups
                WHERE subsite_id = ?";
            $res = Pdb::q($q, [$subsite_id], 'pdo');

            self::$names[$subsite_id] = array();
            foreach ($res as $row) {
                self::$names[$subsite_id][$row['page_id'] . '_' . $row['position']] = $row;
            }
            $res->closeCursor();
        }

        if (!isset(self::$extras[$subsite_id])) {
            $q = "SELECT extra.page_id, extra.text, image.filename AS image
                FROM ~menu_extras AS extra
                LEFT JOIN ~files AS image ON extra.image = image.id
                WHERE extra.subsite_id = ?";
            $res = Pdb::q($q, [$subsite_id], 'pdo');

            self::$extras[$subsite_id] = array();
            foreach ($res as $row) {
                self::$extras[$subsite_id][$row['page_id']] = $row;
            }
        }

        return self::$names[$subsite_id];
    }


    /**
    * Return an array of group names to use for a given top parent
    * ADMIN ONLY METHOD
    *
    * @param TreeNode $page_id The top-parent page id
    * @return array Group IDs and names
    **/
    public static function getGroupsAdmin($page_id)
    {
        $names = self::loadGroupNames($_SESSION['admin']['active_subsite']);

        $groups = Subsites::getConfigAdmin('nav_groups');
        if (! $groups) {
            return array();
        }

        // Determine number of groups to offer
        $num = $groups[$page_id];
        if (! $num) {
            return array();
        }

        $out = array();
        for ($position = 1; $position <= $num; ++$position) {
            $key = $page_id . '_' . $position;

            // If there isn't a db record, create one
            if (! isset($names[$key])) {
                $update_data = array();
                $update_data['subsite_id'] = (int)$_SESSION['admin']['active_subsite'];
                $update_data['page_id'] = (int)$page_id;
                $update_data['position'] = (int)$position;
                $update_data['date_added'] = Pdb::now();
                $update_data['date_modified'] = Pdb::now();
                $update_data['name'] = self::$fallback_names[$position];

                $menu_group_id = Pdb::insert('menu_groups', $update_data);
                $names[$key] = [
                    'id' => $menu_group_id,
                    'name' => self::$fallback_names[$position],
                ];
            }

            $out[$names[$key]['id']] = $names[$key];
        }

        return $out;
    }


    /**
    * Get all group details, grouped by top parent page id
    * ADMIN ONLY METHOD
    **/
    public static function getAllGroupsAdmin()
    {
        $groups = Subsites::getConfigAdmin('nav_groups');

        $all_groups = array();
        foreach ($groups as $page_id => $num) {
            $all_groups[$page_id] = self::getGroupsAdmin($page_id);
        }

        return $all_groups;
    }


    /**
    * Get all group names, grouped by top parent page id
    * ADMIN ONLY METHOD
    **/
    public static function getAllNamesAdmin()
    {
        $groups = Subsites::getConfigAdmin('nav_groups');

        $all_names = array();
        foreach ($groups as $page_id => $num) {
            $groups = self::getGroupsAdmin($page_id);

            $names = [];
            foreach ($groups as $id => $row) {
                $names[$id] = $row['name'];
            }

            $all_names[$page_id] = $names;
        }

        return $all_names;
    }


    /**
    * Returns an array of position -> name for all groups for a page
    **/
    public static function getAllNames($page_id)
    {
        $names = self::loadGroupNames();

        $out = array();
        foreach ($names as $key => $group) {
            preg_match('!^([0-9]+)_([0-9]+)$!', $key, $matches);
            if ($matches[1] == $page_id) {
                $out[$matches[2]] = $group['name'];
            }
        }

        return $out;
    }

    /**
    * Return the group id for a given page and position
    **/
    public static function getId($page_id, $position)
    {
        $names = self::loadGroupNames();
        return $names[$page_id . '_' . $position]['id'];
    }

    /**
    * Return the group name for a given page and position
    **/
    public static function getName($page_id, $position)
    {
        $names = self::loadGroupNames();
        return $names[$page_id . '_' . $position]['name'];
    }

    /**
    * Return an array of Treenode instances for the items in this group
    **/
    public static function getItems($page_id, $position, $limit = null)
    {
        $names = self::loadGroupNames();

        $menu_group = $names[$page_id . '_' . $position]['id'];
        if ($menu_group == null) return array();

        $root = Navigation::getRootNode();
        $root->filterChildren(new TreenodeInMenuMatcher());

        $items = $root->findAllNodes(new TreenodeValueMatcher('menu_group', $menu_group));

        if ($limit) {
            $items = array_slice($items, 0, $limit);
        }

        $root->removeFilter();

        return $items;
    }


    /**
    * Get all extras, grouped by top parent page id
    * ADMIN ONLY METHOD
    **/
    public static function getAllExtrasAdmin() {
        self::loadGroupNames($_SESSION['admin']['active_subsite']);
        return self::$extras[$_SESSION['admin']['active_subsite']];
    }


    /**
    * Return an array of extra menu details for a top-level nav items
    *
    * Return keys:
    *    text    Description
    *    image   Image filename
    *
    * @param int $page_id Top-level page ID
    * @return array
    **/
    public static function getExtras($page_id) {
        self::loadGroupNames();
        return self::$extras[SubsiteSelector::$subsite_id][$page_id];
    }

}
