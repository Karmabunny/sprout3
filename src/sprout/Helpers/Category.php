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

use InvalidArgumentException;

use karmabunny\pdb\Exceptions\RowMissingException;
use karmabunny\pdb\Exceptions\ConstraintQueryException;


class Category
{

    /**
    * Creates a category.
    * Returns the id or NULL on failure.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param string $name The name of the category to add
    **/
    public static function create($table, $name)
    {
        $update_data = array();
        $update_data['name'] = $name;
        $update_data['date_added'] = Pdb::now();
        $update_data['date_modified'] = Pdb::now();
        return Pdb::insert(self::tableMain2cat($table), $update_data);
    }


    /**
    * Looks up a category.
    * Returns the id, or NULL if not found.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param string $name The name of the category to find
    * @param bool $case_sensitive True for a case sensitive match. Default false
    **/
    public static function lookup($table, $name, $case_sensitive = false)
    {
        $table_cat = self::tableMain2cat($table);

        $conditions = [];
        if ($case_sensitive) {
            $conditions[] = ['name', '=', $name];

        } else {
            $conditions[] = ['name', 'CONTAINS', $name];
        }

        $params = [];
        $where = Pdb::buildClause($conditions, $params);
        $q = "SELECT id FROM ~{$table_cat} WHERE {$where}";
        try {
            return Pdb::q($q, $params, 'val');
        } catch (RowMissingException $ex) {
            return null;
        }
    }


    /**
    * Looks up a category. If not found, creates the category.
    * Returns the id or NULL on failure.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param string $name The name of the category to find or add
    * @param bool $case_sensitive True for a case sensitive match. Default false
    **/
    public static function lookupOrCreate($table, $name, $case_sensitive = false)
    {
        $id = self::lookup($table, $name, $case_sensitive);

        if (! $id) {
            $id = self::create($table, $name);
        }

        return $id;
    }


    /**
    * Get the name of a category based on the category id
    * Returns the name, or NULL if not found.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param string $id The ID of the category to get the name of
    * @return string|null The category name or null if not found
    **/
    /**
     * @param string $table
     * @param int|string $id
     * @return string|null
     */
    public static function name($table, $id)
    {
        $id = (int) $id;

        $table_cat = self::tableMain2cat($table);

        $q = "SELECT name FROM ~{$table_cat} WHERE id = ?";
        try {
            return Pdb::q($q, [$id], 'val');
        } catch (RowMissingException $ex) {
            return null;
        }
    }


    /**
     * Converts a main table name into a category table name.
     * Assumes standard naming conventions.
     * @throws InvalidArgumentException If the table name is not a valid identifier
     * @param string $table Main table name, e.g. 'articles'
     * @return string Category table name, e.g. 'articles_cat_list'
     */
    public static function tableMain2cat($table)
    {
        // if this is valid then return value will be
        Pdb::validateIdentifier($table ?: '');
        return $table . '_cat_list';
    }


    /**
     * Converts a main table name into a joiner table name.
     * Assumes standard naming conventions.
     * @throws InvalidArgumentException If the table name is not a valid identifier
     * @param string $table Main table name, e.g. 'articles'
     * @return string Joiner table name, e.g. 'articles_cat_join'
     */
    public static function tableMain2joiner($table)
    {
        Pdb::validateIdentifier($table ?: '');
        return $table . '_cat_join';
    }


    /**
     * Converts a main table name into a joiner column name.
     * Assumes standard naming conventions.
     * @throws InvalidArgumentException If the table name is not a valid identifier
     * @param string $table Main table name, e.g. 'articles'
     * @return string Joiner column name, e.g. 'article_id'
     */
    public static function columnMain2joiner($table)
    {
        Pdb::validateIdentifier($table ?: '');
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        return implode('_', $words) . '_id';
    }


    /**
    * Converts a category table (e.g. 'articles_cat_list') into a main table (e.g. 'articles')
    * Assumes standard naming conventions.
    * @throws InvalidArgumentException If the table name is not a valid identifier
    * @param string $table Category table name, e.g. 'articles_cat_list'
    * @return string Main table name, e.g. 'articles'
    **/
    public static function tableCat2main($table)
    {
        if ($table == '') throw new InvalidArgumentException();
        $words = explode('_', str_replace('_cat_list', '', $table));
        $words[count($words)-1] = Inflector::plural($words[count($words)-1]);
        Pdb::validateIdentifier(implode('_', $words));
        return implode('_', $words);
    }


    /**
     * Inserts a record into a category.
     * @param string $table The 'main' table (e.g. 'articles')
     * @param int $record_id The id in the main table.
     * @param int $category_id The id in the category table.
     * @return bool True if adding the record to the category succeeded
     */
    public static function insertInto($table, $record_id, $category_id)
    {
        $table_joiner = self::tableMain2joiner($table);
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        $record_col = implode('_', $words) . '_id';
        $category_col = 'cat_id';

        $update_data = array();
        $update_data[$record_col] = $record_id;
        $update_data[$category_col] = $category_id;

        try {
            Pdb::insert($table_joiner, $update_data);
        } catch (ConstraintQueryException $ex) {
            return false;
        }

        return true;
    }


    /**
    * Removes a record from a category.
    * Returns true on success and false on failure
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param int $record_id The id in the main table.
    * @param int $category_id The id in the category table.
    **/
    public static function removefrom($table, $record_id, $category_id)
    {
        $table_joiner = self::tableMain2joiner($table);
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        $record_col = implode('_', $words) . '_id';
        $category_col = 'cat_id';

        $conditions = array();
        $conditions[$record_col] = $record_id;
        $conditions[$category_col] = $category_id;

        try {
            Pdb::delete($table_joiner, $conditions);
        } catch (ConstraintQueryException $ex) {
            return false;
        }

        return true;
    }


    /**
    * Does a given category contatin a given record?
    * Returns true if found, false otherwise
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param int $record_id The id in the main table.
    * @param int $category_id The id in the category table.
    **/
    public static function contains($table, $record_id, $category_id)
    {
        $record_id = (int) $record_id;
        $category_id = (int) $category_id;

        $table_joiner = self::tableMain2joiner($table);
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        $record_col = implode('_', $words) . '_id';
        $category_col = 'cat_id';

        $q = "SELECT 1 FROM ~{$table_joiner}
            WHERE {$record_col} = ? AND {$category_col} = ?";
        $res = Pdb::q($q, [$record_id, $category_id], 'arr');

        if (count($res) == 0) return false;

        return true;
    }


    /**
    * Return an array of IDs of records in a given category.
    * If there are no records, returns an empty array.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param int $category_id The id in the category table.
    **/
    public static function recordList($table, $category_id)
    {
        $category_id = (int) $category_id;

        $table_joiner = self::tableMain2joiner($table);
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        $record_col = implode('_', $words) . '_id';
        $category_col = 'cat_id';

        $q = "SELECT {$record_col} AS id FROM ~{$table_joiner} WHERE {$category_col} = ?";
        return Pdb::q($q, [$category_id], 'col');
    }


    /**
    * Return an array of IDs of categories a record is in.
    * If there are no categories, returns an empty array.
    *
    * @param string $table The 'main' table (e.g. 'articles')
    * @param int $record_id The id in the main table.
    **/
    public static function categoryList($table, $record_id)
    {
        $record_id = (int) $record_id;

        $table_joiner = self::tableMain2joiner($table);
        $words = explode('_', $table);
        $words[count($words)-1] = Inflector::singular($words[count($words)-1]);
        $record_col = implode('_', $words) . '_id';

        $q = "SELECT cat_id AS id FROM ~{$table_joiner} WHERE {$record_col} = ?";
        return Pdb::q($q, [$record_id], 'col');
    }

}

