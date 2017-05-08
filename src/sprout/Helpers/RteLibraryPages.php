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
* Richtext library for pages
**/
class RteLibraryPages extends RteLibrary
{
    protected $name = 'Pages';
    private $db;


    public function __construct()
    {
    }


    /**
    * Do a library browse
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public function browse($path)
    {

        if ($path) {
            $path_parts = explode('/', $path);
            $parent_id = array_pop($path_parts);
        } else {
            $parent_id = 0;
        }

        $root = Navigation::loadPageTree($_SESSION['admin']['active_subsite'], true);
        $node = $root->findNodeValue('id', $parent_id);

        $out = array();

        // This page
        if ($parent_id != 0) {
            $out[] = new RteLibObject(
                $node['id'],
                $node['name'],
                array(
                    'href' => 'page/view_by_id/' . $node['id'],
                    'title' => $node['name'],
                ),
                array(
                    'date' => $node['date_modified'],
                )
            );
        }

        // Children pages
        foreach ($node->children as $row) {
            if (count($row->children)) {
                $out[] = new RteLibContainer(
                    $row['id'],
                    $row['name']
                );

            } else {
                $out[] = new RteLibObject(
                    $row['id'],
                    $row['name'],
                    array(
                        'href' => 'page/view_by_id/' . $row['id'],
                        'title' => $row['name'],
                    ),
                    array(
                        'date' => $row['date_modified'],
                    )
                );
            }
        }

        return $out;
    }


    /**
    * Do a library search
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public function search($term)
    {
        $conditions = array();
        $conditions[] = ['pages.name', 'CONTAINS', $term];

        $params = [];
        $where = Pdb::buildClause($conditions, $params, 'OR');

        $q = "SELECT pages.id, pages.name, pages.date_modified
            FROM ~pages AS pages
            WHERE pages.active = 1
              AND ({$where})
            ORDER BY pages.name";
        $res = Pdb::query($q, $params, 'pdo');

        $out = array();
        foreach ($res as $row) {
            $out[] = new RteLibObject(
                $row['id'],
                $row['name'],
                array(
                    'href' => Page::url($row['id']),
                    'title' => $row['name'],
                ),
                array(
                    'date' => $row['date_modified'],
                )
            );
        }
        $res->closeCursor();

        return $out;
    }

}
