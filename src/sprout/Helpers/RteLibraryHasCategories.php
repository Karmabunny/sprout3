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
* Abstract richtext library for bog-standard "has categories" modules, such as news articles, blog posts, etc.
**/
abstract class RteLibraryHasCategories extends RteLibrary
{
    protected $name;

    /**
     * @var string Main table name
     */
    protected $main_table;

    /**
     * @var array Columns to pull when fetching records from the main table
     */
    protected $main_columns = ['id', 'name'];

    /**
     * @var array Columns for the ORDER BY clause
     */
    protected $main_order = ['name'];

    /**
     * @var array Columns to search against
     */
    protected $search_columns = ['name'];


    /**
     * For a given database row, return the display identifier of the record
     */
    protected function getIdentifier(array $row)
    {
        return $row['name'];
    }


    /**
     * For a given database row, return an array of link attributes
     *
     * @param array $row From the database
     * @return array HTML attributes, e.g. 'href' or 'title'
     */
    protected abstract function getLinkAttrs(array $row);


    /**
     * Validates configuration
     */
    public function __construct()
    {
        Pdb::validateIdentifier($this->main_table);

        foreach ($this->main_columns as $col) {
            Pdb::validateIdentifier($col);
        }
        foreach ($this->main_order as $col) {
            Pdb::validateIdentifier($col);
        }
        foreach ($this->search_columns as $col) {
            Pdb::validateIdentifier($col);
        }

        if (!in_array('id', $this->main_columns)) {
            $this->main_columns[] = 'id';
        }
        if (!in_array('date_modified', $this->main_columns)) {
            $this->main_columns[] = 'date_modified';
        }
    }


    /**
     * Do a library browse
     *
     * @return array of RteLibContainer and RteLibObject objects
     */
    public function browse($path)
    {
        $cat_table = Category::tableMain2cat($this->main_table);
        $joiner_table = Category::tableMain2joiner($this->main_table);
        $joiner_col = Category::columnMain2joiner($this->main_table);

        if ($path == '') {
            // An empty "path", so return the category list
            $q = "SELECT cat.id, cat.name
                FROM ~{$cat_table} AS cat
                INNER JOIN ~{$joiner_table} AS joiner ON joiner.cat_id = cat.id
                INNER JOIN ~{$this->main_table} AS main ON joiner.{$joiner_col} = main.id
                GROUP BY cat.id
                ORDER BY cat.name";
            $res = Pdb::query($q, [], 'pdo');

            $out = array();
            foreach ($res as $row) {
                $out[] = new RteLibContainer($row['id'], $row['name']);
            }
            $res->closeCursor();

            return $out;

        } else {
            // Return the item list
            $columns = implode(',', $this->main_columns);
            $order = implode(',', $this->main_order);
            $q = "SELECT {$columns}
                FROM ~{$this->main_table} AS main
                INNER JOIN ~{$joiner_table} AS joiner ON joiner.{$joiner_col} = main.id
                WHERE joiner.cat_id = ?
                ORDER BY {$order}";
            $res = Pdb::query($q, [$path], 'pdo');

            $out = array();
            foreach ($res as $row) {
                $out[] = new RteLibObject(
                    $row['id'],
                    $this->getIdentifier($row),
                    $this->getLinkAttrs($row),
                    array(
                        'date' => $row['date_modified'],
                    )
                );
            }
            $res->closeCursor();

            return $out;
        }
    }


    /**
     * Do a library search
     *
     * @return array of RteLibObject objects
     */
    public function search($term)
    {
        $conditions = [];
        foreach ($this->search_columns as $col) {
            $conditions[] = [$col, 'CONTAINS', $term];
        }

        $params = [];
        $where = Pdb::buildClause($conditions, $params, 'OR');

        $columns = implode(',', $this->main_columns);
        $order = implode(',', $this->main_order);
        $q = "SELECT {$columns}
            FROM ~{$this->main_table} AS main
            WHERE {$where}
            ORDER BY {$order}";
        $res = Pdb::query($q, $params, 'pdo');

        $out = array();
        foreach ($res as $row) {
            $out[] = new RteLibObject(
                $row['id'],
                $this->getIdentifier($row),
                $this->getLinkAttrs($row),
                array(
                    'date' => $row['date_modified'],
                )
            );
        }
        $res->closeCursor();

        return $out;
    }

}
